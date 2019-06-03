<?php

mysqli_report(MYSQLI_REPORT_STRICT); 

class MySQLFunctions extends PersistentStorageFunctions
{
    private $MySQLi;
    private $DatabasePrefix;
    
    public function __construct(MySQLi $MySQLi, $AppendGlobalDatabasePrefix)
    {
        $this->MySQLi = $MySQLi;
        if($AppendGlobalDatabasePrefix == 'Yes') {
            $this->DatabasePrefix = getGlobalDatabasePrefix();
        } else {
            $this->DatabasePrefix = '';   
        }
    }
    
    public function transaction_begin()
    {
        $this->MySQLi->autocommit(FALSE);
    }
    public function transaction_rollback()
    {
        $this->MySQLi->rollback();
    }
    public function transaction_commit()
    {
        $this->MySQLi->commit();
    }

    private function query($sql)
    {
        try {
            $result = $this->MySQLi->query($sql);
        } catch (Exception $e) {
            throw new Exception('There was an error running the SQL query.\n\nError Code : ' . $e->getCode() . ' Error Message : ' . $e->getMessage() . '\n\nSQL : '.$sql);
        }
        return $result;
    }
    
    public function row_add($TableName, $Columns, $Values)
    {
        $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($TableName);
        if(isset($Columns) && is_array($Columns)) {
            $Values = $this->convertAllValuesFromPHPToMySQLFormat($TableName, $Columns, $Values);
            // Escape the $Columns after passing it through the 'convertAllValuesFromPHPToMySQLFormat' function because that function escapes it again.
            $Columns = $this->escapeColumns($Columns);
            
            $sql = "Insert Into ".$EscapedDatabaseAndTableName." (".implode(",", $Columns).") Values (".implode(",", $Values).")";
        } else {
            // Insert empty column.
            $sql = "Insert Into ".$EscapedDatabaseAndTableName . " () VALUES()";
        }
                
        $this->query($sql);
        return $this->MySQLi->insert_id;
    }
    public function row_edit($TableName, $Columns, $Values, $Where)
    {   
        $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($TableName);
        $SetString = $this->getSetString($TableName, $Columns, $Values);
        $WhereString = $this->getWhereString($Where);
            
        $sql = "Update ".$EscapedDatabaseAndTableName." Set ".$SetString.$WhereString;
        $this->query($sql);
    }
    public function row_delete($TableName, $Where)
    {
        $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($TableName);
        $WhereString = $this->getWhereString($Where);
        $sql = "Delete From ".$EscapedDatabaseAndTableName.$WhereString;
        $this->query($sql);
    }
    public function row_read($TableName, $Columns, $Where, $OrderBy = '', $OrderByAscOrDesc = '', $Limit = '', $Offset = '', $Join = '')
    {
        $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($TableName);
        $Columns = $this->escapeColumns($Columns);
        $JoinString = $this->getJoinString($Join, $EscapedDatabaseAndTableName);
        $WhereString = $this->getWhereString($Where);
        $OrderByString = $this->getOrderByString($OrderBy, $OrderByAscOrDesc);
        $LimitString = $this->getLimitString($Limit);
        $OffsetString = $this->getOffsetString($Offset);
        
        if($Columns == 'All') {
            $sql = "SELECT *";
        } else {
            $sql = "SELECT ".implode(",", $Columns);
        }
        $sql .= " From ".$EscapedDatabaseAndTableName.$JoinString.$WhereString.$OrderByString.$LimitString.$OffsetString;
        
        $result = $this->query($sql);
        return $this->resultToArray($result);
    }
    public function row_copyFromNonArchivesToArchives($TableName, $Row_ID)
    {
        // Copies to and from the archives; if ever amend these two fns then need to make sure it is copying the entire row, as in the App we only reference Live fields but of course here we want to copy the data for Archived fields also in case the field is reactivated and the field has data that was previously entered before it was archived.
        
        $EscapedDatabaseAndTableNameA = $this->getEscapedDatabaseAndTableName($TableName);
        $EscapedDatabaseAndTableNameB = $this->getEscapedDatabaseAndTableName($TableName.'Archives');
        
        $Row_ID = $this->escapeInt($Row_ID);
        
        $CurrentDateTime = date("Y-m-d H:i:s");
        
        $sql = "Insert Into ".$EscapedDatabaseAndTableNameB." Select *, '', '', '', '', '$CurrentDateTime' From ".$EscapedDatabaseAndTableNameA." WHERE ID=".$Row_ID;
        $this->query($sql);
        return $this->MySQLi->insert_id;
    }  
    public function row_copyFromArchivesToNonArchives($TableName, $ArchiveRow_ID)
    {
        // Copies to and from the archives; if ever amend these two fns then need to make sure it is copying the entire row, as in the App we only reference Live fields but of course here we want to copy the data for Archived fields also in case the field is reactivated and the field has data that was previously entered before it was archived.
        
        // I have to do this differently to row_copyFromNonArchivesToArchives() above because you can't insert into a table with less columns than the source via that method.
        $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($TableName.'Archives');
        $ArchiveRow_ID = $this->escapeInt($ArchiveRow_ID);
        $sql = "Select * From ".$EscapedDatabaseAndTableName." WHERE ArchiveRow_ID=".$ArchiveRow_ID;
        $result = $this->query($sql);
        $result = $this->resultToArray($result)[0];
        
        $Counter = 0;
        $FieldsToRemove = ['Deleted_User_ID', 'Deleted_Action_ID', 'Deleted', 'ArchiveRow_ID', 'Archive_Date_Created'];
        foreach($result as $FieldName => $Value) {
            if(!in_array($FieldName, $FieldsToRemove)) {
                $Columns[$Counter] = $FieldName;
                $Values[$Counter] = $Value;
                $Counter++;
            }
        }
        return $this->row_add($TableName, $Columns, $Values);
    }
    
    public function row_getNumberOfRows($TableName, $Where) {
        // https://stackoverflow.com/questions/1893424/count-table-rows
        $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($TableName);
        $WhereString = $this->getWhereString($Where);
        $sql = "SELECT COUNT(*) FROM  ".$EscapedDatabaseAndTableName.$WhereString;
        $result = $this->query($sql);
        return $this->resultToArray($result)[0]['COUNT(*)'];
    }
    
    public function table_create($TableName, $RegularOrArchiveOrIDsStructure)
    {
        $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($TableName);
        
        if($RegularOrArchiveOrIDsStructure == 'Regular')
        {
            // Keep 'PRIMARY KEY' on 'ID' even though we don't use AUTO_INCREMENT on it because we still want it to be indexed.
            $sql = "CREATE TABLE ".$EscapedDatabaseAndTableName." (
            ID INT(11) UNSIGNED PRIMARY KEY,
            Date_Created DATETIME DEFAULT CURRENT_TIMESTAMP,
            Version_Datetime DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            Version_User_ID VARCHAR(25) NOT NULL,
            Version_Action_ID VARCHAR(25) NOT NULL,
            Version INT(11) UNSIGNED NOT NULL
            )";
            $this->query($sql);
        }
        elseif($RegularOrArchiveOrIDsStructure == 'Archive')
        {
            // ArchiveRow_ID has to go last due to row_copyFromNonArchivesToArchives() above.
            $sql = "CREATE TABLE ".$EscapedDatabaseAndTableName." (
            ID INT(11) UNSIGNED,
            Date_Created VARCHAR(50) NOT NULL,
            Version_Datetime VARCHAR(50) NOT NULL,
            Version_User_ID VARCHAR(25) NOT NULL,
            Version_Action_ID VARCHAR(25) NOT NULL,
            Version INT(11) UNSIGNED NOT NULL,
            Deleted_User_ID VARCHAR(25) NOT NULL,
            Deleted_Action_ID VARCHAR(25) NOT NULL,
            Deleted VARCHAR(8) NOT NULL,
            ArchiveRow_ID INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            Archive_Date_Created DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
            $this->query($sql);
        }
        elseif($RegularOrArchiveOrIDsStructure == 'IDs')
        {
            // I use the table 'TableNameIDs' (e.g. Object_1IDs) for each table (and when adding a record I add to this IDs table first to get the ID to use in the regular table) because MySQL tables lose the auto-increment value if you deleted the last record in a table and then restart the server as detailed here https://stackoverflow.com/questions/18692068/will-mysql-reuse-deleted-ids-when-auto-increment-is-applied & https://dba.stackexchange.com/questions/16602/prevent-reset-of-auto-increment-id-in-innodb-database-after-server-restart. (I will never delete records from these 'IDs' tables and therefore the auto increment integrity will be maintained.) (I don't need an IDs table for the Archives (i.e. to maintain integrity for ArchiveRow_ID) because I never delete records from the Archives table, but if I ever changed the system design in some way and started to delete records from the Archives table then I would need a 'TableNameArchivesIDs' table also.)
            $sql = "CREATE TABLE ".$EscapedDatabaseAndTableName." (
            ID INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY
            )";
            $this->query($sql);
        }
    }
    public function table_delete($TableName)
    {
        $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($TableName);
        
        /*
            $sql = "DROP TABLE ".$EscapedDatabaseAndTableName;
            $this->query($sql);
        */
        throw new Exception("I have disabled 'MySQLFunctions.php/table_delete' for now as it should never need to be called and I would not want to call it by accident");
    }
    public function table_updateName($OldTableName, $NewTableName)
    {
        $EscapedDatabaseAndTableName_Old = $this->getEscapedDatabaseAndTableName($OldTableName);
        $EscapedDatabaseAndTableName_New = $this->getEscapedDatabaseAndTableName($NewTableName);
        
        $sql = "RENAME TABLE ".$EscapedDatabaseAndTableName_Old." TO ".$EscapedDatabaseAndTableName_New;
        $this->query($sql);
    }
    
    public function table_addColumn($TableName, $ColumnName, $StorageType, $ColumnLength)
    {        
        $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($TableName);
        
        // Check that the ColumnName doesn't have a space in it. (Spaces should be replaced by underscores.)
        if(preg_match('/\s/',$ColumnName))
        {
            throw new Exception("Space in the column name. TableName : '" . $TableName . "' ColumnName : '" . $ColumnName . "'");
        }
        
        $ColumnName = $this->escapeIdent($ColumnName);
        
        $this->whiteListStorageType($StorageType);
                    
        $StorageType = $this->getStorageTypeFormat($StorageType, $ColumnLength);

        $sql = "ALTER TABLE ".$EscapedDatabaseAndTableName." ADD ".$ColumnName." ".$StorageType." NOT NULL"; // Must add 'NOT NULL' otherwise it causes errors in places.
        $this->query($sql);
    }
    public function table_addIndexToColumn($TableName, $ColumnName)
    {
        if($this->table_doesIndexExistOnColumn($TableName, $ColumnName) === true) {
            throw new Exception('An index already exists on this column "' . $ColumnName . '" on table "' . $TableName . '"');
        }
        
        $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($TableName);
        
        // Check that the ColumnName doesn't have a space in it. (Spaces should be replaced by underscores.)
        if(preg_match('/\s/',$ColumnName))
        {
            throw new Exception("Space in the column name. TableName : '" . $TableName . "' ColumnName : '" . $ColumnName . "'");
        }
        
        $ColumnName = $this->escapeIdent($ColumnName);
        
        $sql = "ALTER TABLE ".$EscapedDatabaseAndTableName." ADD INDEX(".$ColumnName.")";
        $this->query($sql);
    }
    public function table_doesIndexExistOnColumn($TableName, $ColumnName)
    {    
        $TableName = $this->DatabasePrefix . $TableName;
        $Response = $this->splitTableNameToGetDatabase($TableName);
            
        $Database = $this->escapeString($Response['Database']);
        $TableName = $this->escapeString($Response['TableName']);
        $ColumnName = $this->escapeString($ColumnName);
        
        $sql = "SELECT COUNT(1) IndexIsThere FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema=".$Database." AND table_name=".$TableName." AND index_name=".$ColumnName;
        $result = $this->query($sql);
        
        $result2 = $this->resultToArray($result);
        
        if(!isset($result2[0]['IndexIsThere'])) {
            throw new Exception($sql);
        }
        
        if($result2[0]['IndexIsThere'] == '0') {
            return false;
        } else {
            return true;
        }
    }
    public function table_deleteColumn($TableName, $ColumnName)
    {
        $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($TableName);
        $ColumnName = $this->escapeIdent($ColumnName);
        
        /*
        $sql = "ALTER TABLE ".$EscapedDatabaseAndTableName." DROP COLUMN ".$ColumnName;
        $this->query($sql);
        */
        throw new Exception("I have disabled 'MySQLFunctions.php/table_deleteColumn' for now as it should never need to be called and I would not want to call it by accident");
    }
    public function table_updateColumnName($TableName, $OldColumnName, $NewColumnName, $StorageType, $ColumnLength)
    {
        $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($TableName);
        $OldColumnName = $this->escapeIdent($OldColumnName);
        $NewColumnName = $this->escapeIdent($NewColumnName);
        $this->whiteListStorageType($StorageType);
        
        $StorageType = $this->getStorageTypeFormat($StorageType, $ColumnLength);
        $sql = "ALTER TABLE ".$EscapedDatabaseAndTableName." CHANGE ".$OldColumnName." ".$NewColumnName." ".$StorageType." NOT NULL"; // Must add 'NOT NULL' otherwise it causes errors in places.
        $this->query($sql);
    }
    public function table_getColumnStorageType($TableName, $ColumnName)
    {
        $TableName = $this->DatabasePrefix . $TableName;
        $Response = $this->splitTableNameToGetDatabase($TableName);
        
        $Database = $this->escapeString($Response['Database']);
        $TableName = $this->escapeString($Response['TableName']);
        $ColumnName = $this->escapeString($ColumnName);
            
        $sql = "SELECT Data_Type, CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns WHERE table_schema = ".$Database." AND table_name = ".$TableName." AND column_name = ".$ColumnName;
        $result = $this->query($sql);
                
        $result2 = $this->resultToArray($result);
        
        if(!isset($result2[0]['Data_Type'])) {
            throw new Exception($sql);
        }
        
        $Response['StorageType'] = $result2[0]['Data_Type'];
        $Response['ColumnMaxLength'] = $result2[0]['CHARACTER_MAXIMUM_LENGTH'];
        
        return $Response;
    }
    public function table_getColumnStorageTypeAndTypeData($TableName, $ColumnName)
    {
        $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($TableName);
        $ColumnName = $this->escapeString($ColumnName);
        
        $sql = "SHOW COLUMNS FROM ".$EscapedDatabaseAndTableName." LIKE ".$ColumnName;
        $result = $this->query($sql);
        return $this->resultToArray($result)[0]['Type'];
    }
    public function table_updateColumnStorageType($TableName, $ColumnName, $StorageType, $ColumnLength)
    {
        $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($TableName);
        $ColumnName = $this->escapeIdent($ColumnName);
        $this->whiteListStorageType($StorageType);
        
        $StorageType = $this->getStorageTypeFormat($StorageType, $ColumnLength);
                
        $sql = "ALTER TABLE ".$EscapedDatabaseAndTableName." MODIFY ".$ColumnName." ".$StorageType." NOT NULL";
        $this->query($sql);
    }
    public function table_getColumnOrder($TableName)
    {
        $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($TableName);
        
        $sql = "SHOW COLUMNS FROM ".$EscapedDatabaseAndTableName;
        $result = $this->query($sql);
        $result = $this->resultToArray($result);
         
        for($i=0; $i<sizeof($result); $i++) {
            $ColumnOrder[$i] = $result[$i]['Field'];
        }
        return $ColumnOrder;
    }
    public function table_reorderColumn($TableName, $ColumnName, $StorageType, $PreviousColumnName)
    {
        $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($TableName);
        $ColumnName = $this->escapeIdent($ColumnName);
        $PreviousColumnName = $this->escapeIdent($PreviousColumnName);
        $this->whiteListStorageType($StorageType);
        
        $sql = "ALTER TABLE ".$EscapedDatabaseAndTableName." MODIFY COLUMN ".$ColumnName." ".$StorageType." NOT NULL AFTER ".$PreviousColumnName; // Must add 'NOT NULL' otherwise it causes errors in places.
        $this->query($sql);
    }
    
    public function table_checkTableExists($TableName)
    {
        $TableName = $this->DatabasePrefix . $TableName;
        $Response = $this->splitTableNameToGetDatabase($TableName);
        
        $Database = $this->escapeIdent($Response['Database']);
        $TableName = $this->escapeString($Response['TableName']);
        
        $sql = "SHOW TABLES FROM ".$Database." LIKE ".$TableName;
        $result = $this->query($sql);
        if($result->num_rows > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    public function table_checkColumnExists($TableName, $ColumnName)
    {
        $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($TableName);
        $ColumnName = $this->escapeString($ColumnName);
        
        $sql = "SHOW COLUMNS FROM ".$EscapedDatabaseAndTableName." LIKE ".$ColumnName;
        $result = $this->query($sql);
        if($result->num_rows > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public function database_checkDatabaseExists($DatabaseName)
    {
        $DatabaseName = $this->DatabasePrefix . $DatabaseName;
        $DatabaseName = $this->escapeString($DatabaseName);
        
        $sql = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ".$DatabaseName;
        $result = $this->query($sql);
        if($result->num_rows > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
        
    }
    
    private function getEscapedDatabaseAndTableName($TableName)
    {
        $TableName = $this->DatabasePrefix . $TableName;
        $Response = $this->splitTableNameToGetDatabase($TableName);
        return $this->escapeIdent($Response['Database']) . '.' . $this->escapeIdent($Response['TableName']);
    }
    
    private function splitTableNameToGetDatabase($TableName)
    {        
        $TableNameSpliter = explode('.', $TableName);
        $Database = $TableNameSpliter[0];
        
        $TableNameSpliter = removeKeyFromArray($TableNameSpliter, '0');
        $TableName = implode('.', $TableNameSpliter);
        
        $Response['Database'] = $Database;
        $Response['TableName'] = $TableName;
        
        return $Response;
    }
    
    private function escapeColumns($Columns) {
        if($Columns != 'All') {
            for($i=0; $i<sizeof($Columns); $i++) {
                // For Joins I append the TableName to each Column so I now need to escape that accordingly if that is so.
                if(strpos($Columns[$i], '.') !== false) {
                    $Spliter = explode('.', $Columns[$i]);
                    $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($Spliter[0].'.'.$Spliter[1]);
                    $Column = $this->escapeIdent($Spliter[2]);
                    $Columns[$i] = $EscapedDatabaseAndTableName . '.' . $Column;
                } else {
                    $Columns[$i] = $this->escapeIdent($Columns[$i]);
                }
            }
        }
        return $Columns;
    }
    
    private function getJoinString($Join, $EscapedDatabaseAndTableName) {
        if($Join != '') {
            $EscapedDatabaseAndTableName2 = $this->getEscapedDatabaseAndTableName($Join['Table2']);        
            $EscapedTable1Field = $this->escapeIdent($Join['Table1_Field']);
            $EscapedTable2Field = $this->escapeIdent($Join['Table2_Field']);
            
            $JoinString = " INNER JOIN ".$EscapedDatabaseAndTableName2." ON ".$EscapedDatabaseAndTableName2.".".$EscapedTable2Field." = ".$EscapedDatabaseAndTableName.".".$EscapedTable1Field;
        } else {
            $JoinString = '';
        }
        return $JoinString;
    }
    
    private function getWhereString($Where) {
        if($Where != '') {
            $WhereString = " WHERE " . $this->convertWhereArrayToSQL($Where);
        } else {
            $WhereString = '';
        }
        return $WhereString;
    }
    
    private function getOrderByString($OrderBy, $OrderByAscOrDesc) {
        if($OrderBy != '') {
            if($OrderByAscOrDesc == '') {
                $OrderByAscOrDesc = 'ASC';
            }
            if($OrderByAscOrDesc !== 'DESC' && $OrderByAscOrDesc !== 'ASC') {
                throw new Exception('Incorrect OrderByAscOrDesc : ' . $OrderByAscOrDesc);   
            }
            $OrderByString = " ORDER BY ".$this->escapeIdent($OrderBy)." ".$OrderByAscOrDesc;
        } else {
            $OrderByString = '';
        }
        return $OrderByString;
    }
    
    private function getLimitString($Limit) {
        if($Limit != '') {
            return " LIMIT ".$this->escapeInt($Limit);
        } else {
            return '';
        }
    }
    
    private function getOffsetString($Offset) {
        if($Offset != '' && $Offset != '0') {
            return " OFFSET ".$this->escapeInt($Offset);
        } else {
            return '';
        }
    }
    
    private function getSetString($TableName, $Columns, $Values)
    {
        if(sizeof($Columns) != sizeof($Values)) {
            throw new Exception('Size of Columns does not equal size of Values');
        }
        
        for($i=0; $i<sizeof($Columns); $i++) {
            $SetData[$Columns[$i]] = $Values[$i];
        }
        
        return $this->createSET($TableName, $SetData);
    }
    
    private function convertAllValuesFromPHPToMySQLFormat($TableName, $Columns, $Values)
    {
        if(sizeof($Columns) != sizeof($Values))
        {
            throw new Exception('Number of Columns does not equal number of Fields.');
        }   
        for($i=0; $i<sizeof($Columns); $i++)
        {
            $Values[$i] = $this->convertThisValueFromPHPToMySQLFormat($TableName, $Columns[$i], $Values[$i]);
        }
        return $Values;
    }
    
    private function convertThisValueFromPHPToMySQLFormat($TableName, $Column, $Value)
    {
        $StorageTypeAndColumnMaxLength = $this->table_getColumnStorageType($TableName, $Column);
        $this->validateColumnMaxLength($StorageTypeAndColumnMaxLength['ColumnMaxLength'], $Value);
        return call_user_func_array([$this, 'PHPToMySQL_'.$StorageTypeAndColumnMaxLength['StorageType']], array($Value));
    }
    
    private function validateColumnMaxLength($ColumnMaxLength, $Value) {
        if($ColumnMaxLength != '' && $ColumnMaxLength != '0') {
            if(strlen($Value) > $ColumnMaxLength) {
                throw new Exception('Column Max Length Exceeded.');
            }
        }
    }
    
    private function getStorageTypeFormat($StorageType, $ColumnLength)
    {
        if($ColumnLength != '') {
            $StorageType .= '('.$ColumnLength.')';
        }
        
        // Set 250 as the column length for VarChar if none set above via $ColumnLength.
        if($StorageType == 'VarChar' || $StorageType == 'varchar')
        {
            $StorageType .= '(250)'; // http://stackoverflow.com/questions/3156815/why-does-varchar-need-length-specification
        }
        
        return $StorageType;
    }
    
    private function whiteListStorageType($StorageType)
    {
        $StorageType = strtolower($StorageType);
        
        if($StorageType != 'decimal(18,2)') {
            // Remove "(x)".
            $StorageType = preg_replace('/[0-9]+/', '', $StorageType); // Remove all numbers to account for the x.
            $StorageType = str_replace("()", "", $StorageType);
        }
        
        $Allowed = array('int', 'int', 'float', 'decimal(18,2)', 'boolean', 'varchar', 'varchar', 'text', 'date', 'datetime', 'timestamp', 'time', 'tinyint');
        $Found = array_search($StorageType, $Allowed);
        
        if($Found === FALSE) {
            throw new Exception("StorageType " . $StorageType . " Is Not Allowed.");   
        }
    }

    private function convertWhereArrayToSQL($holder)
    {        
        $sql = '( ';

        if(isset($holder['Conditions']))
        {
            $sql .= $this->convertWhereConditionsToSQL($holder['Conditions']);
        }
        elseif(isset($holder['Groups']))
        {
            if(isset($holder['Groups']['Seperator']))
            {
                $NumberOfGroups = sizeof($holder['Groups']) - 1; // The last one is the 'Seperator'
            }
            else
            {
                $NumberOfGroups = sizeof($holder['Groups']);
            }
            
            if($NumberOfGroups < 1)
            {
                throw new Exception('NumberOfGroups less than 1');
            }
            
            if($NumberOfGroups > 1)
            {
                if(!isset($holder['Groups']['Seperator']) || $holder['Groups']['Seperator'] == '' || !isset($holder['Groups']['Seperator']))
                {
                    throw new Exception('Seperator not set');
                }

                if($holder['Groups']['Seperator'] !== 'AND' && $holder['Groups']['Seperator'] !== 'OR')
                {
                    throw new Exception('Seperator neither AND nor OR');
                }
            }

            for($i=0; $i<$NumberOfGroups; $i++)
            {
                if($i > 0)
                {
                    $sql .= ' ' . $holder['Groups']['Seperator'] . ' ';    
                }
                
                if(!$holder['Groups'][$i] || $holder['Groups'][$i] == '' || !isset($holder['Groups'][$i]) || !is_array($holder['Groups'][$i]))
                {
                    throw new Exception('Groups number ' . $i . ' not set');
                }

                $sql .= $this->convertWhereArrayToSQL($holder['Groups'][$i]);
            }
        }
        else
        {
            throw new Exception('No Groups');
        }

        $sql .= ' )';
        
        if(isset($holder['NegateEntireGroup']) && $holder['NegateEntireGroup'] == 'Yes') {
            $sql = '!'.$sql; 
        }

        return $sql;
    }

    // should < etc be restricted to only number fields?
    private function convertWhereConditionsToSQL($Conditions)
    {   
        $sql = '';

        if(sizeof($Conditions) < 1)
        {
            throw new Exception('Less than one condition');
        }

        for($i=0; $i<sizeof($Conditions); $i++)
        {
            if($i > 0)
            {
                $sql .= ' And ';    
            }
            if(!isset($Conditions[$i]['FieldName']) || $Conditions[$i]['FieldName'] == '')
            {
                throw new Exception('FieldName not set');
            }
            
            if(!isset($Conditions[$i]['Comparison']) || $Conditions[$i]['Comparison'] == '')
            {
                throw new Exception('Comparison not set');
            }
            
            $FieldName = $Conditions[$i]['FieldName'];
            // For Joins I append the TableName to each FieldName so I now need to escape that accordingly if that is so.
            if(strpos($FieldName, '.') !== false) {
                $Spliter = explode('.', $FieldName);
                $EscapedDatabaseAndTableName = $this->getEscapedDatabaseAndTableName($Spliter[0].'.'.$Spliter[1]);
                $FieldNameHolder = $this->escapeIdent($Spliter[2]);
                $FieldName = $EscapedDatabaseAndTableName . '.' . $FieldNameHolder;
            } else {
                $FieldName = $this->escapeIdent($FieldName);
            }
            
            $Comparison = $Conditions[$i]['Comparison'];
            
            if(isset($Conditions[$i]['Value'])) {
                $Conditions[$i]['Value1'] = $Conditions[$i]['Value'];
            }
            
            if(!isset($Conditions[$i]['Value1']) && ($Comparison != 'IsNull' && $Comparison != 'IsNotNull')) {
                throw new Exception('Value1 not set');
            }
            if(!isset($Conditions[$i]['Value2']) && ($Comparison == 'Between' && $Comparison == 'NotBetween')) {
                throw new Exception('Value2 not set');
            }
            
            if(isset($Conditions[$i]['Value_Type']) && $Conditions[$i]['Value_Type'] == 'Field_ID') {
                throw new Exception('Value_Type must be string by the time it gets to here.');
            }
            
            // We need to add the percentage mark before escaping the string below, so that the quotes go around the value and the percentage mark.
            if($Comparison == 'StartsWith' || $Comparison == 'DoesNotStartWith')
            {
                $Conditions[$i]['Value1'] = $Conditions[$i]['Value1'] . '%';
            }
            elseif($Comparison == 'EndsWith' || $Comparison == 'DoesNotEndWith')
            {
                $Conditions[$i]['Value1'] = '%' . $Conditions[$i]['Value1'];
            }
            elseif($Comparison == 'Contains' || $Comparison == 'DoesNotContain')
            {
                $Conditions[$i]['Value1'] = '%' . $Conditions[$i]['Value1'] . '%';
            }
            
            // Use escapeString for all of the below as opposed to escapeInt, because even the '<' operators etc could take decimals as values and decimals are classed as strings for escaping purposes.
            if(isset($Conditions[$i]['Value1']))
            {
                if($Comparison == 'In' || $Comparison == 'NotIn') { // Values should be comma seperated
                    $String = '';
                    $Spliter = explode(",", $Conditions[$i]['Value1']);
                    foreach($Spliter as $Value1) {
                        if($String != '') {
                            $String .= ',';   
                        }
                        $String .= $this->escapeString($Value1);
                    }
                    $Conditions[$i]['Value1'] = $String;
                } else {
                    $Conditions[$i]['Value1'] = $this->escapeString($Conditions[$i]['Value1']);
                }
            }
            if(isset($Conditions[$i]['Value2']))
            {
                $Conditions[$i]['Value2'] = $this->escapeString($Conditions[$i]['Value2']);
            }
                        
            if($Comparison == '=' || $Comparison == '!=')
            {
                $sql .= $FieldName . ' ' . $Comparison . ' ' . $Conditions[$i]['Value1'];
            }
            elseif($Comparison == '<' || $Comparison == '<=' || $Comparison == '>' || $Comparison == '>=')
            {
                $sql .= $FieldName . ' ' . $Comparison . ' ' . $Conditions[$i]['Value1'];
            }
            elseif(in_array($Comparison, array('StartsWith', 'EndsWith', 'Contains', 'DoesNotStartWith', 'DoesNotEndWith', 'DoesNotContain')))
            {
                if($Comparison == 'StartsWith' || $Comparison == 'EndsWith' || $Comparison == 'Contains')
                {
                    $sql .= $FieldName . ' LIKE ' . $Conditions[$i]['Value1']; // % is added above.
                }
                elseif($Comparison == 'DoesNotStartWith' || $Comparison == 'DoesNotEndWith' || $Comparison == 'DoesNotContain')
                {
                    $sql .= $FieldName . ' NOT LIKE ' . $Conditions[$i]['Value1']; // % is added above.
                }
            }
            elseif($Comparison == 'In') // Values should be comma seperated
            {
                $sql .= $FieldName . " IN (" . $Conditions[$i]['Value1'] . ")";
            }
            elseif($Comparison == 'NotIn') // Values should be comma seperated
            {
                $sql .= $FieldName . " NOT IN (" . $Conditions[$i]['Value1'] . ")";
            }
            elseif($Comparison == 'Between')
            {
                $sql .= $FieldName . " BETWEEN " . $Conditions[$i]['Value1'] . " AND " . $Conditions[$i]['Value2'];
            }
            elseif($Comparison == 'NotBetween')
            {
                $sql .= $FieldName . " NOT BETWEEN " . $Conditions[$i]['Value1'] . " AND " . $Conditions[$i]['Value2'];
            }
            elseif($Comparison == 'IsNull') // I.e. is empty
            {
                $sql .= $FieldName . ' IS NULL';
            }
            elseif($Comparison == 'IsNotNull') // I.e. is not empty
            {
                $sql .= $FieldName . ' IS NOT NULL';
            }
            elseif($Comparison == 'WithinLastXHours') // Only works for Datetime fields
            {
                $sql .= $FieldName . ' > DATE_SUB(NOW(), INTERVAL '.$Conditions[$i]['Value1'].' HOUR)';
            }
            else
            {
                throw new Exception('Incorrect Comparison value');
            }
        }
        
        return $sql;
    }

    private function resultToArray($result)
    {
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC); // http://stackoverflow.com/questions/25605292/alternative-to-mysqli-fetch-all-needed
        return $rows;
    }
    
    /* 
    In this class I need to handle conversions for strings, dates etc into the correct format from how it is stored in MySQL to how I store them in PHP.
    Note that these functions should do no validation, that is done elsewhere, we assume here that the value is in the correct format already and then we just convert the format and escape it correctly.
    */
    // Don't have MySQLToPHP functions as collecting 'table_getColumnStorageType' for every column for every read massively affects performance (literally holds up when loading a page) and so I use the 'DefaultViewAs' value live in the app to manage this instead.
    private function PHPToMySQL_Int($Int)
    {
        return $this->escapeInt($Int);
    }
    private function PHPToMySQL_Decimal($Decimal)
    {
        validateIsNumeric($Decimal);
        return $this->escapeString($Decimal);
    }
    private function PHPToMySQL_Float($Float)
    {
        validateIsNumeric($Float);
        return $this->escapeString($Float);
    }
    private function PHPToMySQL_tinyint($Boolean)
    {
        validateBoolean($Boolean);
        return $this->escapeInt($Boolean);
    }
    private function PHPToMySQL_VarChar($VarChar) 
    {
        validateIsLessThan250Characters($VarChar);
        return $this->escapeString($VarChar);
    }
    private function PHPToMySQL_Text($Text) 
    {
        return $this->escapeString($Text);
    }
    private function PHPToMySQL_Date($Date)
    {
        validateDate($Date);
        return $this->escapeString($Date);
    }
    private function PHPToMySQL_DateTime($DateTime)
    {
        validateDateTime($DateTime);
        return $this->escapeString($DateTime);
    }
    private function PHPToMySQL_TimeStamp($TimeStamp)
    {
        validateTimeStamp($TimeStamp);
        return $this->escapeString($TimeStamp);
    }
    private function PHPToMySQL_Time($Time)
    {
        validateTime($Time);
        return $this->escapeString($Time);
    }
    
    /* 
    The following escape functions are taken from (I have slightly modified them) colshrapnel/safemysql;
    @author col.shrapnel@gmail.com
    @link http://phpfaq.ru/safemysql
    https://github.com/colshrapnel/safemysql
    */
    private function escapeInt($value)
	{
        validateInteger($value);
        if(empty($value))
        {
            return "''";
        }
        if($value === NULL)
		{
			return 'NULL';
		}
		return $value;
	}
	private function escapeString($value) // strings (also DATE, FLOAT and DECIMAL)
	{
        if(is_array($value) || is_object($value)) {
            throw new Exception('Array received but string expected.');
        }
		if($value === NULL)
		{
			return 'NULL';
		}
		return "'".$this->MySQLi->real_escape_string($value)."'";
	}
	private function escapeIdent($value) // identifiers (table and field names) 
	{
        if(is_array($value)) {
            throw new Exception('Array received but string expected.');
        }
		if($value)
		{
            return "`".str_replace("`","``",$value)."`";
		} else {
            throw new Exception('Empty value for identifier');
		}
	}
	private function createIN($data) // complex array for IN() operator (string of 'a','b','c' format, without parentesis)
	{
		if (!is_array($data))
		{
            throw new Exception('Value for IN should be array');
		}
		if (!$data)
		{
			return 'NULL';
		}
		$query = $comma = '';
		foreach ($data as $value)
		{
			$query .= $comma.$this->escapeString($value);
			$comma  = ",";
		}
		return $query;
	}
	private function createSET($TableName, $data) // complex array for SET operator (string of `field`='value',`field`='value' format)
	{
		if (!is_array($data))
		{
            throw new Exception('SET expects array, ' . gettype($data) . ' given');
		}
		if (!$data)
		{
            throw new Exception('Empty array for SET');
		}
		$query = $comma = '';
		foreach ($data as $key => $value)
		{
			$query .= $comma.$this->escapeIdent($key).'='.$this->convertThisValueFromPHPToMySQLFormat($TableName, $key, $value);
			$comma  = ",";
		}
		return $query;
	}
}