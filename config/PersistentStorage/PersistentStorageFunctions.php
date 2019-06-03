<?php

// All forms of persistant storage I use are structured in a table, row format with tables and archive tables. All rows must have the columns as stated in MySQLFunctions.php->table_create().

abstract class PersistentStorageFunctions
{
    abstract public function transaction_begin();
    abstract public function transaction_rollback();
    abstract public function transaction_commit();
    
    abstract public function row_add($TableName, $Columns, $Values);
    abstract public function row_edit($TableName, $Columns, $Values, $Where);
    abstract public function row_delete($TableName, $Row_ID);
    abstract public function row_read($TableName, $Columns, $Where, $OrderBy = '', $OrderByAscOrDesc = '', $Limit = '', $Offset = '', $Join = '');
    abstract public function row_copyFromNonArchivesToArchives($TableName, $Row_ID);
    abstract public function row_copyFromArchivesToNonArchives($TableName, $ArchiveRow_ID);
    abstract public function row_getNumberOfRows($TableName, $Where);
    
    abstract public function table_create($TableName, $RegularOrArchiveStructure);
    abstract public function table_delete($TableName);
    abstract public function table_updateName($OldTableName, $NewTableName);
    
    abstract public function table_addColumn($TableName, $ColumnName, $StorageType, $ColumnLength);
    abstract public function table_addIndexToColumn($TableName, $ColumnName);
    abstract public function table_doesIndexExistOnColumn($TableName, $ColumnName);
    abstract public function table_deleteColumn($TableName, $ColumnName);
    abstract public function table_updateColumnName($TableName, $OldColumnName, $NewColumnName, $StorageType, $ColumnLength);
    abstract public function table_getColumnStorageType($TableName, $ColumnName);
    abstract public function table_updateColumnStorageType($TableName, $ColumnName, $StorageType, $ColumnLength);
    abstract public function table_getColumnOrder($TableName);
    abstract public function table_reorderColumn($TableName, $ColumnName, $StorageType, $PreviousColumnName);
    
    abstract public function table_checkTableExists($TableName);
    abstract public function table_checkColumnExists($TableName, $ColumnName);
    abstract public function database_checkDatabaseExists($DatabaseName);
}