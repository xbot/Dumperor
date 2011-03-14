<?php
/**
 * Global common settings file
 **/

// Common column type names
$commonTypes = array(
    'numeric'=>'number',
    'decimal'=>'number',
    'float'=>'number',
    'nvarchar'=>'varchar',
    'nvarchar2'=>'varchar',
    'varchar2'=>'varchar',
    'nchar2'=>'char',
    'char2'=>'char',
    'nchar'=>'char',
    'clob'=>'text',
    'blob'=>'text',
    'long'=>'text',
    'date'=>'datetime',
    'time'=>'datetime',
    'timestamp'=>'datetime',
);

// Common column structures
$commonStructs = array(
    'number,6,0'=>'smallint,0,0',
    'decimal,6,0'=>'smallint,0,0',
    'numeric,6,0'=>'smallint,0,0',
    'number,0,0'=>'integer,0,0',
    'number,10,0'=>'integer,0,0',
);
?>
