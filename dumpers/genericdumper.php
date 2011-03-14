<?php
/**
 * @author Li Dong <lenin.lee@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php
 **/
include_once 'tablestructure.php';

/**
 * Generic Dumper Class
 **/
abstract class GenericDumper
{
    var $objDB;                 // object, database connector
    var $strDBName;             // string, database name
    var $arrTableName;          // array, table name
    var $arrEscapeTable;        // array, table names to get rid of
    var $arrEscapeColumn;       // array, table-columns pairs
    var $arrIncludeTable;       // array, tables to be exported
    var $arrTable;              // array, table-table structure pairs
    var $arrCommonCond;         // array, key=>value array, common conditions used when generating select statements
    var $arrCommonEscCol;       // array, common escape columns
    var $arrFakeData;           // array, two-dimentional, table-column-data pairs
    var $intLimit;              // integer, controls how many lines will be retrieved from the database for each table
    var $commonTypeNames;       // array, common column type names
    var $commonColumnStructs;   // array, common column structures
    
    function __construct($objDB, $strDBName)
    {
        $this->objDB = $objDB;
        $this->strDBName = $strDBName;
        $this->arrTableName = array();
        $this->arrEscapeTable = array();
        $this->arrEscapeColumn = array();
        $this->arrIncludeTable = array();
        $this->arrTable = array();
        $this->arrCommonCond = array();
        $this->arrCommonEscCol = array();
        $this->arrFakeData = array();
        $this->intLimit = 0;
        $this->commonTypeNames = array();
        $this->commonColumnStructs = array();
    }

    /**
     * Dump names of all the tables in the given database, except those to be escaped
     *
     * @return array One-dimensional array, holding table names dumped from the database
     **/
    abstract function DumpTableNames();

    /**
     * Dump name,type,length of each field from the given table in the given database, except those to be escaped
     *
     * @param string Table name
     * @return array Two-dimensional array, containing three sub arrays: names, types and lengths
     **/
    abstract function DumpColumnFullInfo($strTableName);

    /**
     * Dump name,type,length of each field from the given table in the given database, except those to be escaped
     *
     * @param string Table name
     * @return array Two-dimensional array, containing three sub arrays: names, types and lengths
     **/
    abstract function DumpColumnBriefInfo($strTableName);

    /**
     * Dump primary key constraint
     *
     * @param string Table name
     * @return object instance of TableConstraint
     **/
    abstract function DumpPrimaryConstraint($strTableName);

    /**
     * Dump unique constraint
     *
     * @param string Table name
     * @return array instances of TableConstraint
     **/
    abstract function DumpUniqueConstraints($strTableName);

    /**
     * Dump table structure
     *
     * @param string Table name
     * @return object Table structure instance
     **/
    function DumpTableStructure($strTableName)
    {
        $objStruct = false;

        $arrMeta = $this->DumpColumnFullInfo($strTableName);
        if (count($arrMeta) == 6) {
            $objStruct = new TableStructure($strTableName);
            $objStruct->SetColumnNames($arrMeta[0]);
            $objStruct->SetColumnTypes($arrMeta[1]);
            $objStruct->SetColumnLengths($arrMeta[2]);
            $objStruct->SetColumnPrecisions($arrMeta[3]);
            $objStruct->SetColumnNullables($arrMeta[4]);
            $objStruct->SetColumnDefaults($arrMeta[5]);

            $objStruct->SetPrimaryConstraint($this->DumpPrimaryConstraint($strTableName));
            $objStruct->SetUniqueConstraints($this->DumpUniqueConstraints($strTableName));
        }

        return $objStruct;
    }

    function DumpTableInfo($strTableName)
    {
        $struct = array();

        $arrFields = $this->DumpColumnBriefInfo($strTableName);
        ksort($arrFields);

        $struct['name'] = strtolower($strTableName);
        $struct['fields'] = $arrFields;
        //$struct['pk'] = $this->DumpPrimaryConstraint($strTableName);
        //$struct['uk'] = $this->DumpUniqueConstraints($strTableName);

        return $struct;
    }

    /**
     * Dump all data
     *
     * @param string Output file path, if none given, all sql statements will be sent to the stdio device
     * @param boolean Whether to output the given data to the standard output device along with the output file, this param goes meanningless if $strOutputFile is false
     * @return int Number of lines dumped
     **/
    function Dump($strOutputFile=false, $boolForce=false)
    {
        $numTotal = 0;
        $arrTableName = $this->DumpTableNames();

        foreach ($arrTableName as $strTableName) {
            $numLines = $this->DumpTable($strTableName, $strOutputFile, $boolForce);
            $numTotal += $numLines;
        }

        return $numLines;
    }

    /**
     * Dump all data of the given table
     *
     * @param string Table name
     * @param string Output file path
     * @param boolean Whether to output the given data to the standard output device along with the output file, this param goes meanningless if $strOutputFile is false
     * @return int Number of lines dumped
     **/
    function DumpTable($strTableName, $strOutputFile=false, $boolForce=false)
    {
        $numLines = 0;
        $objStruct = $this->DumpTableStructure($strTableName);
        $strSQLSlct = $this->GenerateSelectStmt($objStruct);
        $strSQLDel = $this->GenerateDeleteStmt($objStruct);

        //$this->Output($strSQLSlct);

        $this->Output($strSQLDel, $strOutputFile, $boolForce);

        $rs = $this->objDB->query($strSQLSlct);
        if ($rs) {
            while (($arrRow = $this->objDB->read($rs)) !== false) {
                $strSQLIst = $this->GenerateInsertStmt($objStruct, $arrRow);
                $numLines++;
                $this->Output($strSQLIst, $strOutputFile, $boolForce);
            }
            $this->objDB->free($rs);
        }

        // Append a batch seperator
        $strSep = $this->GetBatchSeperator();
        $this->Output($strSep, $strOutputFile, $boolForce);

        return $numLines;
    }

    /**
     * Dump all data out of the given table for comparation
     *
     * @param string Table name
     * @param string Output file path
     * @param boolean Whether to output the given data to the standard output device along with the output file, this param goes meanningless if $strOutputFile is false
     * @return int Number of lines dumped
     **/
    function DumpRawData($strTableName, $strOutputFile=false, $boolForce=false)
    {
        $numLines = 0;
        $objStruct = $this->DumpTableStructure($strTableName);
        $strSQLSlct = $this->GenerateSelectStmt($objStruct);

        $this->Output($strTableName, $strOutputFile, $boolForce);
        $this->Output('----------', $strOutputFile, $boolForce);

        $rs = $this->objDB->query($strSQLSlct);
        if ($rs) {
            while (($arrRow = $this->objDB->read($rs)) !== false) {
                $numLines++;

                $cols = array();
                foreach ($arrRow as $key=>$val) {
                    //TODO: fix me
                    $cols[] = strtolower($key)."=>".str_replace(array("\n","\r","\r\n"), '', $val);
                }
                $this->Output(implode(',', $cols), $strOutputFile, false);
            }
            $this->objDB->free($rs);
        }

        $this->Output('', $strOutputFile, $boolForce);

        return $numLines;
    }

    /**
     * Output the given data to the specified output device
     *
     * @param mixed Data to be output
     * @param mixed Output device, FALSE indicates the standard output device, but a string indicates the output file
     * @param boolean Whether to output the given data to the standard output device along with the output file, this param goes meanningless if $mixOutput is false
     **/
    function Output($mixData, $mixOutput=false, $boolForce=false)
    {
        $mixData = trim($mixData.'')."\n";

        if (strlen(trim($mixOutput.'')) > 0) {
            file_put_contents($mixOutput, $mixData, FILE_APPEND);
            if (true === $boolForce) {
                echo isset($_SERVER['HTTP_USER_AGENT']) ? htmlspecialchars($mixData) : $mixData;
            }
        } else if (false === $mixOutput) {
            echo isset($_SERVER['HTTP_USER_AGENT']) ? htmlspecialchars($mixData) : $mixData;
        }
    }

    /**
     * Output the given table structure by using the Output method in a human readable format
     *
     * @param object TableStructure instance
     * @param string The full path of the output file
     * @return void
     **/
    public function OutputTableStructure($structure, $filePath=false)
    {
        // Template for column
        $tmpl = "Column: %s; Type: %s; Length: %d; Precision: %d;";

        if ($structure instanceof TableStructure) {
            // Output the table name
            $this->Output('Table: '.$structure->GetTableName(), $filePath, true);
            // Output columns
            $structure->ResetIterator();

            $arr = array();
            while ($col = $structure->GetNextCol()) {
                $col = $this->GetCommonColumnStructure($col);
                $colStr = sprintf($tmpl, strtolower($col['name']), $col['type'], $col['length'], $col['precision']);
                $arr[] = $colStr;
            }

            sort($arr);
            foreach ($arr as $colStr) {
                $this->Output($colStr, $filePath, true);
            }
        } else {
            $this->Output('OutputTableStructure() needs an instance of TableStructure, '.gettype($structure).' given.', $filePath, true);
        }
    }

    /**
     * Check if the given column type is char type
     **/
    public static function IsCharCol($strType)
    {
        $arrType = array('VARCHAR', 'NVARCHAR', 'CHAR', 'NCHAR', 'NVARCHAR2', 'VARCHAR2', 'LONGTEXT', 'TEXT', 'NTEXT');

        return in_array(strtoupper($strType), $arrType);
    }

    /**
     * Check if the given column type is date type
     **/
    public static function IsDateCol($strType)
    {
        $arrType = array('DATETIME','DATE','TIMESTAMP','SMALLDATE','TIME', 'SMALLDATETIME');

        return in_array(strtoupper($strType), $arrType);
    }

    /**
     * Check if the given column type is lob type
     **/
    public static function IsLobCol($strColType)
    {
        $arr = array('BLOB', 'CLOB', 'NBLOB', 'NCLOB');

        return in_array(strtoupper($strColType), $arr);
    }    

    /**
     * Check if the given column type is number type
     **/
    public static function IsNumCol($strType)
    {
        $arrType = array('NUMERIC', 'INT', 'SMALLINT', 'FLOAT', 'DECIMAL', 'NUMBER', 'MONEY', 'SMALLMONEY', 'TINYINT', 'BIT', 'BIGINT', 'REAL');

        return in_array(strtoupper($strType), $arrType);
    }

    /**
     * Generate select statement for fetching data
     *
     * @param object Table structure
     * @return string SQL statement
     * @fixme Add more functions to common conditions
     **/
    function GenerateSelectStmt($objStruct)
    {
        $sql = "select ";

        // Generate sql statement
        $objStruct->ResetIterator();
        while ($arr = $objStruct->GetNextCol()) {
            // Escape columns which are needless
            if (in_array(strtolower($arr['name']), $this->arrCommonEscCol)) {
                continue;
            }

            // Hook subclass method to populate the column
            if ($this->hookColHandler($sql, $arr)) {
                continue;
            }

            // If the column is set to be fake, just do it
            $strFakeSlct = $this->GetFakeString($objStruct->GetTableName(), $arr);
            if (strlen($strFakeSlct)>0) {
                $sql .= "$strFakeSlct as ".$arr['name'].",";
                continue;
            }

            if (self::IsDateCol($arr['type']))
                $sql .= $this->GetSelectDateStr('tbl', $arr['name']).',';
            else
                $sql .= "tbl.".$arr['name']." as ".$arr['name'].",";
        }

        $sql = substr($sql, 0, strlen($sql)-1)." from ".$objStruct->GetTableName()." tbl where 1=1";

        // Consider common conditions
        if (is_array($this->arrCommonCond)) {
            foreach ($this->arrCommonCond as $fld=>$val) {
                $sql .= " and tbl.$fld=$val";
            }
        }

        // Order
        $prm = $objStruct->GetPrimaryConstraint();
        if ($prm instanceof TableConstraint) {
            $cols = $prm->GetColumns();
            if (is_array($cols) && count($cols)>0) {
                foreach ($cols as $k=>$v) {
                    $cols[$k] = 'tbl.'.$v;
                }
                $sql .= " order by ".implode(',', $cols);
            }
        }

        // Set the limit
        if (is_numeric($this->intLimit) && is_int($this->intLimit+0) && $this->intLimit > 0) {
            $sql = $this->HookLimit($sql, $this->intLimit);
        }

        return $sql;
    }

    /**
     * Let subclass decide what format to use for the column, while generating select SQL statement in GenerateSelectStmt()
     *
     * @param string SQL statement
     * @param array Column information
     * @return boolean If the subclass handles this column, return true; else return false
     **/
    abstract function hookColHandler(&$sql, $arrCol);

    /**
     * Generate partition of select statement for date and time fields
     *
     * @param string Table alias
     * @param string Column name
     * @param string Column alias
     * @return string
     **/
    abstract function GetSelectDateStr($strTableAlias=false, $strColumnName=false, $strColumnAlias=false);

    /**
     * Generate partition of insert statement for date and time fields
     *
     * @param string Column value
     * @return string
     **/
    abstract function GetInsertDateStr($strVal);

    /**
     * Add limitation statement to the given select statement
     *
     * @param string Select statement
     * @param int Limitation
     * @return string Select statement
     **/
    abstract function HookLimit($strSQL, $intLimit);

    /**
     * Generate delete statement for the specified table
     *
     * @param object Table structure
     * @return string SQL statement
     **/
    function GenerateDeleteStmt($objStruct)
    {
        $sql = 'delete from '.$objStruct->GetTableName().' where 1=1';
        if (is_array($this->arrCommonCond)) {
            foreach ($this->arrCommonCond as $fld=>$val) {
                $sql .= " and $fld=$val";
            }
        }

        return $sql.';';
    }

    /**
     * Generate insert statement
     * Need GenerateInsertTemplate() and GenerateInsertParam(),
     *
     * @param object Table structure
     * @param array A row fetched from the database
     * @return string SQL statement
     **/
    function GenerateInsertStmt($objStruct, $arrRow)
    {
        $strTpl = $this->GenerateInsertTemplate($objStruct);
        $strParam = $this->GenerateInsertParam($objStruct, $arrRow);

        $sql = '';
        eval('$sql = sprintf("'.$strTpl.'", '.$strParam.');');
        return $sql.';';
    }

    /**
     * Generate create table statement
     *
     * @param object Table structure
     * @return string SQL statement
     **/
    abstract function GenerateCreateTableStmt($objStruct);

    /**
     * Generate insert statement template for one row of fetched data
     *
     * @param object Table structure
     * @return string SQL statement
     **/
    private function GenerateInsertTemplate($objStruct)
    {
        $sql = false;
        $strClassName = get_class($objStruct);
        if ($strClassName && strtoupper($strClassName) == 'TABLESTRUCTURE') {
            $arrColName = array();
            $arrValHolder = array();

            $objStruct->ResetIterator();
            while ($arrCol = $objStruct->GetNextCol()) {
                //print_r($arrCol);print_r($this->arrCommonEscCol);die;
                // Escape columns which are needless
                if (in_array(strtolower($arrCol['name']), $this->arrCommonEscCol)) {
                    continue;
                }

                $arrColName[] = $arrCol['name'];

                if (self::IsCharCol($arrCol['type'])) {
                    $arrValHolder[] = "N'%s'";
                } else {
                    $arrValHolder[] = '%s';
                }
            }

            $sql = "insert into ".$objStruct->GetTableName()." (".implode(',', $arrColName).") values (".implode(',', $arrValHolder).")";
        }

        return $sql;
    }

    /**
     * Generate a string of parameters to be filled into the insert statement template
     *
     * @param object Table structure
     * @param array A row fetched from the database
     * @return string Param string
     **/
    private function GenerateInsertParam($objStruct, $arrRow)
    {
        $arrParam = array();

        $strClassName = get_class($objStruct);
        if ($strClassName && strtoupper($strClassName) == 'TABLESTRUCTURE') {
            $objStruct->ResetIterator();
            while ($arrCol = $objStruct->GetNextCol()) {
                // Escape columns which are needless
                if (in_array(strtolower($arrCol['name']), $this->arrCommonEscCol)) {
                    continue;
                }

                $val = $arrRow[$arrCol['name']];
                $arrParam[] = $this->GenerateInsertParamCol($arrCol['type'], $val);
            }
        }

        return implode(',', $arrParam);
    }

    /**
     * Generate a string for the given column type and value, which is a part of the values string of an insert statement
     * This method is intended to be called by the method GenerateInsertParam()
     *
     * @param string Column type
     * @param mixed Column value
     * @return string
     **/
    protected function GenerateInsertParamCol($strColType, $mixVal)
    {
        $strParam = '';

        if (self::IsDateCol($strColType)) {
            $strParam = '"'.$this->GetInsertDateStr($mixVal).'"';
        } else if (self::IsNumCol($strColType)) {
            $strParam = strlen(trim($mixVal))==0 ? 0 : $mixVal;
        } else {
            $mixVal = str_replace("'", "''", $mixVal);
            $mixVal = addcslashes($mixVal, "'\0\n\r\t");
            $strParam = strlen(trim($mixVal))==0 ? '""' : "'".$mixVal."'";
        }

        return $strParam;
    }

    /**
     * Generate a part of select statement for the specified column if it is set to be fake
     *
     * @param string Table name
     * @param array Column defination
     * @return string Empty if the column is not set to be fake
     **/
    function GetFakeString($strTableName, $arrColDef)
    {
        if (!isset($this->arrFakeData[$strTableName][$arrColDef['name']])) {
            return '';
        }

        $mixFakeData = $this->arrFakeData[$strTableName][$arrColDef['name']];

        if (self::IsCharCol($arrColDef['type'])) {
            if ($mixFakeData === false) {
                return "'*'";
            } else {
                return "'$mixFakeData'";
            }
        } else if (self::IsDateCol($arrColDef['type'])) {
            if ($mixFakeData === false) {
                return $this->GetSelectDateStr();
            } else {
                return "'$mixFakeData'";
            }
        } else if (self::IsNumCol($arrColDef['type'])) {
            if ($mixFakeData === false) {
                return '0';
            } else {
                return "$mixFakeData";
            }
        } else {
            return "$mixFakeData";
        }
    }

    /**
     * Return the batch script seperator according to the database type
     *
     * @return string
     **/
    abstract function GetBatchSeperator();

    /**
     * Getter of tables' structures, no setter method
     **/
    function GetTables()
    {
        return $this->arrTables;
    }

    /**
     * Getter of the database name
     **/
    function GetDBName()
    {
        return $this->strDBName;
    }

    /**
     * Setter of the database name
     **/
    function SetDBName($strDBName)
    {
        if (is_string($strDBName) && strlen($strDBName)>0) {
            $this->strDBName = $strDBName;
        }
    }

    /**
     * Getter of the array holding table names, no setter for this property
     **/
    function GetTableNames()
    {
        return $this->arrTableName;
    }

    /**
     * Getter of the array holding table names to be escaped
     **/
    function GetEscapeTables()
    {
        return $this->arrEscapeTable;
    }

    /**
     * Setter of the array holding table names to be escaped
     **/
    function SetEscapeTables($arrTableName)
    {
        if (is_array($arrTableName)) {
            $this->arrEscapeTable = $arrTableName;
        }
    }

    /**
     * Comparing with SetEscapeTables(), instead of replacing the property arrEscapeTable with the given one, this method merges them
     **/
    function AddEscapeTables($arrTableName)
    {
        if (is_array($arrTableName)) {
            $this->arrEscapeTable = array_merge($this->arrEscapeTable, $arrTableName);
        }
    }

    /**
     * Getter of the array holding column names to be escaped
     **/
    function GetEscapeColumns($strTableName=false)
    {
        if (false === $strTableName) {
            return $this->arrEscapeColumn;
        }

        if (isset($this->arrEscapeColumn[$strTableName])) {
            return $this->arrEscapeColumn[$strTableName];
        }

        return array();
    }

    /**
     * Setter of the array holding column names to be escaped
     **/
    function SetEscapeColumns($arrEscapeColumn)
    {
        if (is_array($arrEscapeColumn)) {
            $this->arrEscapeColumn = $arrEscapeColumn;
        }
    }

    /**
     * Setter of the array holding column names to be escaped
     **/
    function SetTableEscapeColumns($strTableName, $arrColumn)
    {
        if (is_string($strTableName) && strlen($strTableName)>0 && is_array($arrColumn)) {
            $this->arrEscapeColumn[$strTableName] = $arrColumn;
        }
    }

    /**
     * Setter of the array holding table names to be included
     **/
    function SetIncludeTables($arrTableName)
    {
        if (is_array($arrTableName)) {
            $this->arrIncludeTable = $arrTableName;
        }
    }

    /**
     * Comparing with SetIncludeTables(), instead of replacing the property arrIncludeTable with the given one, this method merges them
     **/
    function AddIncludeTables($arrTableName)
    {
        if (is_array($arrTableName)) {
            $this->arrIncludeTable = array_merge($this->arrIncludeTable, $arrTableName);
        }
    }

    /**
     * Getter of common conditions
     **/
    function GetCommonCond()
    {
        return $this->arrCommonCond;
    }

    /**
     * Setter of common conditions
     **/
    function SetCommonCond($arrCond)
    {
        if (is_array($arrCond)) {
            $this->arrCommonCond = $arrCond;
        }
    }

    /**
     * Getter of common escape columns
     **/
    function GetCommonEscCol()
    {
        return $this->arrCommonEscCol;
    }

    /**
     * Setter of common escape columns
     **/
    function SetCommonEscCol($arrEscCol)
    {
        if (is_array($arrEscCol)) {
            $this->arrCommonEscCol = $arrEscCol;
        }
    }

    /**
     * Getter of fake data
     **/
    function GetFakeData()
    {
        return $this->arrFakeData;
    }

    /**
     * Setter of fake data
     **/
    function SetFakeData($arrFakeData)
    {
        if (is_array($arrFakeData)) {
            $this->arrFakeData = $arrFakeData;
        }
    }

    /**
     * Add an important column whose data won't be dumped
     *
     * @param string Table name
     * @param string Column name
     * @param mixed Fake data, leave it unspecified if you want a default fake value be assigned to it according to the column's type
     * @return none
     **/
    function SetFakeColumn($strTableName, $strColumnName, $mixData=false)
    {
        if (is_string($strTableName) && strlen($strTableName)>0 && is_string($strColumnName) && strlen($strColumnName)>0 && is_scalar($mixData)) {
            $this->arrFakeData[$strTableName][$strColumnName] = $mixData;
        }
    }

    /**
     * Set how many lines will be retrieved from the database for each table
     *
     * @param int Number of lines
     * @return none
     **/
    function SetLimit($intLimit)
    {
        if (is_numeric($intLimit) && is_int($intLimit+0) && $intLimit>=0) {
            $this->intLimit = $intLimit;
        }
    }

    /**
     * Get common column type names
     **/
    public function GetCommonTypeNames()
    {
        return $this->commTypeNames;
    }

    /**
     * Set common column type names
     *
     * @param array Common column type names setting array
     * @return none
     **/
    public function SetCommonTypeNames($names)
    {
        $this->commTypeNames = $names;
    }

    /**
     * Get common column structures
     **/
    public function GetCommonColumnStructs()
    {
        return $this->commColumnStructs;
    }

    /**
     * Set common column structures
     *
     * @param array Common column structures setting array
     * @return none
     **/
    public function SetCommonColumnStructs($structs)
    {
        $this->commColumnStructs = $structs;
    }

    /**
     * Get common name for the given column type
     *
     * @param string Type name
     * @return string Common type name
     **/
    public function GetCommonTypeName($type)
    {
        $type = strtolower($type);
        $commNames = $this->GetCommonTypeNames();
        if (array_key_exists($type, $commNames)) {
            return $commNames[$type];
        }
        return $type;
    }

    /**
     * Get common column structure for comparation
     *
     * @param array An column structure array
     * @return array Common structure array
     **/
    public function GetCommonColumnStructure($struct)
    {
        $commStructs = $this->GetCommonColumnStructs();
        $fingerprint = sprintf("%s,%d,%d", strtolower($struct['type']), $struct['length'], $struct['precision']);
        if (array_key_exists($fingerprint, $commStructs)) {
            $struct['type'] = $commStructs[$fingerprint][0];
            $struct['length'] = $commStructs[$fingerprint][1];
            $struct['precision'] = $commStructs[$fingerprint][2];
        } else {
            $struct['type'] = $this->GetCommonTypeName($struct['type']);
        }
        return $struct;
    }
}
?>
