<?php
/**
 * @author Li Dong <lenin.lee@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php
 **/
include_once 'genericdumper.php';

/**
 * mysql dumper
 **/
class MySQLDumper extends GenericDumper
{
    /**
     * Dump names of all the tables in the given database, except those to be escaped
     *
     * @return array One-dimensional array, holding table names dumped from the database
     **/
    function DumpTableNames()
    {
        $arrTableName = array();
        $strOrderCond = " order by table_name";
        $strEscapeCond = "";
        $strIncludeCond = "";

        if (is_array($this->arrEscapeTable) && count($this->arrEscapeTable)>0) {
            $strEscapeCond = " and table_name not in ('".implode("','", $this->arrEscapeTable)."')";
        }

        if (is_array($this->arrIncludeTable) && count($this->arrIncludeTable)>0) {
            $strIncludeCond = " and table_name in ('".implode("','", $this->arrIncludeTable)."')";
        }

        $sql = "select table_name from information_schema.tables where table_schema='".$this->strDBName."'";
        $sql .= $strEscapeCond;
        $sql .= $strIncludeCond;
        $sql .= $strOrderCond;

        $rs = $this->objDB->query($sql);
        if ($rs) {
            while (($arrRow = $this->objDB->read($rs)) !== false) {
                $arrTableName[] = $arrRow['table_name']; // Case of tablename should be kept as original, otherwise queries for columns' info may fail
            }
            $this->objDB->free($rs);
        }

        sort($arrTableName);

        return $arrTableName;
    }

    function DumpColumnInfo($strTableName)
    {
        $arrName = array();
        $arrType = array();
        $arrLength = array();
        $arrPrecision = array();
        $arrNullable = array();
        $arrDefault = array();

        $arrField = array();
        $strOrderCond = " order by col.ordinal_position";
        //$strOrderCond = " order by col.column_name";

        $sql = "select col.column_name as fld_name,col.column_type as fld_type,col.is_nullable as fld_nullable,ifnull(col.column_default,'') as fld_default";
        $sql .= " from information_schema.columns col";
        $sql .= " where col.table_schema='$this->strDBName' and col.table_name='$strTableName'";
        $sql .= $strOrderCond;

        $rs = $this->objDB->query($sql);
        if ($rs) {
            while (($arrRow = $this->objDB->read($rs)) !== false) {
                $arrName[] = $arrRow['fld_name'];

                // Filtrate column type, length, precision
                $strColumnType = $arrRow['fld_type'];
                // Manipulate column types like decimal(22,0), varchar(128) and so on
                $numIdx1 = strpos($strColumnType, '(');
                $numIdx2 = strpos($strColumnType, ',');
                $numIdx3 = strpos($strColumnType, ')');
                if (false === $numIdx1) {
                    $arrType[] = $strColumnType;
                } else {
                    $arrType[] = substr($strColumnType, 0, $numIdx1);
                }
                // Manipulate column length and precision
                if (false === $numIdx1) {
                    $arrLength[] = 0;
                    $arrPrecision[] = 0;
                } else {
                    if (false === $numIdx2) {
                        $arrLength[] = substr($strColumnType, $numIdx1+1, $numIdx3-$numIdx1-1);
                        $arrPrecision[] = 0;
                    } else {
                        $arrLength[] = substr($strColumnType, $numIdx1+1, $numIdx2-$numIdx1-1);
                        $arrPrecision[] = substr($strColumnType, $numIdx2+1, $numIdx3-$numIdx2-1);
                    }
                }

                $arrNullable[] = $arrRow['fld_nullable'];
                $arrDefault[] = $arrRow['fld_default'];
            }
            $this->objDB->free($rs);
        }

        $arrField = array($arrName, $arrType, $arrLength, $arrPrecision, $arrNullable, $arrDefault);

        return $arrField;
    }

    /**
     * Dump primary key constraint
     *
     * @param string Table name
     * @return object Instance of TableConstraint
     **/
    function DumpPrimaryConstraint($strTableName)
    {
        $strName = '';
        $strType = 'PRIMARY';
        $arrCol = array();

        $sql = "select cnst.constraint_name,cu.column_name";
        $sql .= " from information_schema.table_constraints cnst,information_schema.key_column_usage cu";
        $sql .= " where cnst.constraint_name=cu.constraint_name";
        $sql .= " and cnst.table_schema=cu.table_schema";
        $sql .= " and cnst.table_name=cu.table_name";
        $sql .= " and cnst.constraint_type='primary key'";
        $sql .= " and cnst.table_schema='$this->strDBName' and cnst.table_name='$strTableName'";

        $rs = $this->objDB->query($sql);
        if ($rs) {
            while (($arrRow = $this->objDB->read($rs)) !== false) {
                $strName = $arrRow['constraint_name'];
                $arrCol[] = $arrRow['column_name'];
            }
            $this->objDB->free($rs);
            $objCnst = new TableConstraint($strName, $strType, $arrCol);
        }

        if (0 < count($arrCol)) {
            $objCnst = new TableConstraint($strName, $strType, $arrCol);
            return $objCnst;
        } else {
            return false;
        }
    }

    /**
     * Dump unique constraints
     *
     * @param string Table name
     * @return array Instances of TableConstraint
     **/
    function DumpUniqueConstraints($strTableName)
    {
        $arrCnst = array();

        $sql = "select cnst.constraint_name,cu.column_name";
        $sql .= " from information_schema.table_constraints cnst,information_schema.key_column_usage cu";
        $sql .= " where cnst.constraint_name=cu.constraint_name";
        $sql .= " and cnst.table_schema=cu.table_schema";
        $sql .= " and cnst.table_name=cu.table_name";
        $sql .= " and cnst.constraint_type='unique'";
        $sql .= " and cnst.table_schema='$this->strDBName' and cnst.table_name='$strTableName'";

        $rs = $this->objDB->query($sql);
        if ($rs) {
            while (($arrRow = $this->objDB->read($rs)) !== false) {
                $arrCol = array();

                $strName = $arrRow['constraint_name'];
                $strColumn = $arrRow['column_name'];
                $arrCol[] = $strColumn;

                if (0 == count($arrCnst)) {
                    $objCnst = new TableConstraint($strName, 'UNIQUE', $arrCol);
                } else {
                    $objCnst = array_pop($arrCnst);
                    if ($objCnst->GetName() == $strName) {
                        $objCnst->AppendColumn($strColumn);
                    } else {
                        $objCnst = new TableConstraint($strName, 'UNIQUE', $arrCol);
                    }
                }
                $arrCnst[] = $objCnst;
            }
            $this->objDB->free($rs);
        }

        return $arrCnst;
    }

    /**
     * Return the batch seperator of MySQL
     *
     * @return string
     **/
    function GetBatchSeperator()
    {
        return '';
    }

    /**
     * Generate create table statement
     *
     * @param object Table structure
     * @return string Create table statement
     **/
    public static function GenerateCreateTableStmt($objStruct)
    {
        $strTableBody = self::GenerateCreateTableBodyStmt($objStruct);

        $strSQL = "create table if not exists ".$objStruct->GetTableName()." (\n";
        $strSQL .= $strTableBody;
        $strSQL .= ");";

        return $strSQL;
    }

    /**
     * Generate body of create table statement
     **/
    private static function GenerateCreateTableBodyStmt($objStruct)
    {
        $strBody = "";
        $arrLine = array();

        // Append column names one by one
        $intLen = 0;
        $objStruct->Reset();
        while ($arrCol = $objStruct->GetNextCol()) {
            $strLine = str_repeat(' ', 4).$arrCol['name'];
            $arrLine[] = $strLine;
            if (strlen($strLine) > $intLen) {
                $intLen = strlen($strLine);
            }
        }

        // Append column types one by one
        $intLen += 4;
        $intNewLen = 0;
        $intIdx = 0;
        $objStruct->Reset();
        while ($arrCol = $objStruct->GetNextCol()) {
            $strPrecision = 0 < $arrCol['precision'] ? ",".$arrCol['precision'] : '';
            $strLength = 0 < $arrCol['length'] ? "(".$arrCol['length'].$strPrecision.")" : '';

            $strLine = $arrLine[$intIdx];
            $strLine .= str_repeat(' ', $intLen-strlen($strLine)).$arrCol['type'].$strLength;
            $arrLine[$intIdx] = $strLine;
            if (strlen($strLine) > $intNewLen) {
                $intNewLen = strlen($strLine);
            }
            $intIdx++;
        }

        // Append column nullable information and default values
        $intLen = $intNewLen+4;
        $intIdx = 0;
        $objStruct->Reset();
        while ($arrCol = $objStruct->GetNextCol()) {
            $strLine = $arrLine[$intIdx];

            if (false === $arrCol['nullable']) {
                $strLine .= str_repeat(' ', $intLen-strlen($strLine)).'not null';
                if (false !== $arrCol['default']) {
                    if (self::IsCharCol($arrCol['type'])) {
                        $strLine .= " default '".$arrCol['default']."'";
                    } else if (self::IsNumCol($arrCol['type'])) {
                        $strLine .= " default ".(is_numeric($arrCol['default']) ? $arrCol['default'] : 0);
                    } else if (self::IsDateCol($arrCol['type']) && !is_numeric($arrCol['default']) && 'CURRENT_TIMESTAMP' != $arrCol['default']) {
                        $strLine .= " default '".$arrCol['default']."'";
                    } else {
                        $strLine .= " default ".$arrCol['default'];
                    }
                }
            }

            if ($objStruct->IsSingleUniqueColumn($arrCol['name'])) {
                $strLine .= " unique";
            }

            $strLine .= ",\n";

            $arrLine[$intIdx] = $strLine;
            if (strlen($strLine) > $intNewLen) {
                $intNewLen = strlen($strLine);
            }
            $intIdx++;
        }

        $strBody = implode('', $arrLine);

        // Add primary constraint
        if ($objStruct->HasPrimaryConstraint()) {
            $objCnst = $objStruct->GetPrimaryConstraint();
            $strBody .= str_repeat(' ', 4)."primary key (".implode(',', $objCnst->GetColumns())."),\n";
        }

        // Add clustered unique constraints
        $arrCluster = $objStruct->GetClusteredUniqueConstraints();
        if (count($arrCluster) > 0) {
            foreach ($arrCluster as $objCnst) {
                $strBody .= str_repeat(' ', 4)."unique key '".$objCnst->GetName()."' ('".implode("','", $objCnst->GetColumns())."'),\n";
            }
        }

        $strBody = substr($strBody, 0, strlen($strBody)-2)."\n";

        return $strBody;
    }

    /**
     * Generate partition of select statement for date and time fields
     *
     * @param string Table alias
     * @param string Column name
     * @param string Column alias
     * @return string
     **/
    function GetSelectDateStr($strTableAlias=false, $strColumnName=false, $strColumnAlias=false)
    {
        if (false === $strTableAlias || false === $strColumnName) {
            return "date_format(now(), '%Y-%m-%d %H:%i:%s')";
        }

        $strColumnAlias = false === $strColumnAlias ? $strColumnName : $strColumnAlias;
        return "date_format($strTableAlias.$strColumnName, '%Y-%m-%d %H:%i:%s') as $strColumnAlias";
    }

    /**
     * Generate partition of insert statement for date and time fields
     *
     * @param string Column value
     * @return string
     **/
    function GetInsertDateStr($strVal)
    {
        return "'$strVal'";
    }

    /**
     * Add limitation statement to the given select statement
     *
     * @param string Select statement
     * @param int Limitation
     * @return string Select statement
     **/
    function HookLimit($strSQL, $intLimit)
    {
        return "$strSQL limit 0,$intLimit";
    }

    function hookColHandler(&$sql, $arr)
    {
        return false;
    }
}
?>
