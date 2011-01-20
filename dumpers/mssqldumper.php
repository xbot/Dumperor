<?php
/**
 * @author Li Dong <lenin.lee@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php
 **/
include_once 'genericdumper.php';

/**
 * mssql dumper
 *
 **/
class MSSQLDumper extends GenericDumper
{
    /**
     * Override the construction method of parent
     **/
    function __construct($objDB, $strDBName)
    {
        parent::__construct($objDB, $strDBName);

        $this->AddEscapeTables(array('dtproperties'));
    }

    /**
     * Dump names of all the tables in the given database, except those to be escaped
     *
     * @return array One-dimensional array, holding table names dumped from the database
     **/
    function DumpTableNames()
    {
        $arrTableName = array();
        $strOrderCond = " order by obj.name";
        $strEscapeCond = "";
        $strIncludeCond = "";

        if (is_array($this->arrEscapeTable) && count($this->arrEscapeTable)>0) {
            $strEscapeCond = " and obj.name not in ('".implode("','", $this->arrEscapeTable)."')";
        }

        if (is_array($this->arrIncludeTable) && count($this->arrIncludeTable)>0) {
            $strIncludeCond = " and obj.name in ('".implode("','", $this->arrIncludeTable)."')";
        }

        $sql = "select obj.name from $this->strDBName..sysobjects obj where obj.xtype='U'";
        $sql .= $strEscapeCond;
        $sql .= $strIncludeCond;
        $sql .= $strOrderCond;

        $rs = $this->objDB->query($sql);
        if ($rs) {
            while (($arrRow = $this->objDB->read($rs)) !== false) {
                $arrTableName[] = $arrRow['name'];
            }
            $this->objDB->free($rs);
        }

        return $arrTableName;
    }

    /**
     * Dump name,type,length of each field from the given table in the given database, except those to be escaped
     *
     * @param string Table name
     * @return array Two-dimensional array, containing three sub arrays: names, types and lengths
     **/
    function DumpColumnInfo($strTableName)
    {
        $arrName = array();
        $arrType = array();
        $arrLength = array();
        $arrPrecision = array();
        $arrNullable = array();
        $arrDefault = array();

        $arrField = array();
        $strOrderCond = " order by col.colorder";

        $sql = "select col.name as fld_name,type.name as fld_type,col.prec as fld_length,isnull(col.scale,0) as fld_precision,col.isnullable as fld_nullable,isnull(dft.text,'') as fld_default";
        $sql .= " from $this->strDBName..syscolumns col";
        $sql .= " inner join $this->strDBName..sysobjects obj";
        $sql .= " on col.id=obj.id and obj.xtype='U' and obj.name<>'dtproperties'";
        $sql .= " left join $this->strDBName..systypes type";
        $sql .= " on col.xtype=type.xusertype";
        $sql .= " left join $this->strDBName..syscomments dft";
        $sql .= " on col.cdefault=dft.id";
        $sql .= " where obj.name='$strTableName'";
        $sql .= $strOrderCond;
        //echo htmlspecialchars($sql);

        $rs = $this->objDB->query($sql);
        if ($rs) {
            while (($arrRow = $this->objDB->read($rs)) !== false) {
                $arrName[] = $arrRow['fld_name'];
                $arrType[] = $arrRow['fld_type'];
                $arrLength[] = $arrRow['fld_length'];
                $arrPrecision[] = $arrRow['fld_precision'];
                $arrNullable[] = $arrRow['fld_nullable'];
                $strDefault = trim($arrRow['fld_default']);
                while (strstr($strDefault, '(')) {
                    $strDefault = substr($strDefault, 1, strlen($strDefault)-2);
                }
                $arrDefault[] = $strDefault;
            }
            $this->objDB->free($rs);
        }

        $arrField = array($arrName, $arrType, $arrLength, $arrPrecision, $arrNullable, $arrDefault);

        return $arrField;
    }

    function DumpColumnInfo2($strTableName)
    {
        $st = new TableStructure($strTableName);
        $arrField = array();
        $strOrderCond = " order by col.colorder";

        $sql = "select col.name as fld_name,type.name as fld_type,col.prec as fld_length,isnull(col.scale,0) as fld_precision,col.isnullable as fld_nullable,isnull(dft.text,'') as fld_default";
        $sql .= " from $this->strDBName..syscolumns col";
        $sql .= " inner join $this->strDBName..sysobjects obj";
        $sql .= " on col.id=obj.id and obj.xtype='U' and obj.name<>'dtproperties'";
        $sql .= " left join $this->strDBName..systypes type";
        $sql .= " on col.xtype=type.xusertype";
        $sql .= " left join $this->strDBName..syscomments dft";
        $sql .= " on col.cdefault=dft.id";
        $sql .= " where obj.name='$strTableName'";
        $sql .= $strOrderCond;
        //echo htmlspecialchars($sql);

        $rs = $this->objDB->query($sql);
        if ($rs) {
            while (($arrRow = $this->objDB->read($rs)) !== false) {
                $strDefault = trim($arrRow['fld_default']);
                while (strstr($strDefault, '(')) {
                    $strDefault = substr($strDefault, 1, strlen($strDefault)-2);
                }
                //...
                if ($st->IsNonPrecType($arrRow['fld_type'])) {
                    $arrRow['fld_length'] = '';
                    $arrRow['fld_precision'] = '';
                }
                switch ($arrRow['fld_type']) {
                    case 'nvarchar':
                    case 'varchar':
                    case 'text':
                    case 'char':
                    case 'nchar':
                        $arrRow['fld_type'] = 'text';
                        break;

                    case 'numeric':
                    case 'int':
                    case 'smallint':
                        $arrRow['fld_type'] = 'number';
                        break;

                    case 'datetime':
                    case 'timestamp':
                        $arrRow['fld_type'] = 'date';
                        break;
                    
                    default:
                        // code...
                        break;
                }
                unset($arrRow['fld_default']);
                //$arrRow['fld_nullable'] = $arrRow['fld_nullable'] ? 'YES' : 'NO';
                unset($arrRow['fld_nullable']);
                $arrField[$arrRow['fld_name']] = $arrRow;
            }
            $this->objDB->free($rs);
        }

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

        $sql = "select idx.name as constraint_name,col.name as column_name";
        $sql .= " from $this->strDBName..sysobjects obj,$this->strDBName..sysindexes idx,$this->strDBName..sysindexkeys keys,$this->strDBName..syscolumns col";
        $sql .= " where obj.parent_obj=object_id('$strTableName')";
        $sql .= " and obj.name=idx.name";
        $sql .= " and idx.indid=keys.indid";
        $sql .= " and idx.id=keys.id";
        $sql .= " and keys.id=col.id";
        $sql .= " and keys.colid=col.colid";
        $sql .= " and obj.xtype='PK'";

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

        $sql = "select idx.name as constraint_name,col.name as column_name";
        $sql .= " from $this->strDBName..sysobjects obj,$this->strDBName..sysindexes idx,$this->strDBName..sysindexkeys keys,$this->strDBName..syscolumns col";
        $sql .= " where obj.parent_obj=object_id('$strTableName')";
        $sql .= " and obj.name=idx.name";
        $sql .= " and idx.indid=keys.indid";
        $sql .= " and idx.id=keys.id";
        $sql .= " and keys.id=col.id";
        $sql .= " and keys.colid=col.colid";
        $sql .= " and obj.xtype='UQ'";

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
     * Return the batch seperator of Microsoft SQL Server
     *
     * @return string
     **/
    function GetBatchSeperator()
    {
        return 'go';
    }

    /**
     * Generate create table statement
     *
     * @param object Table structure
     * @return string Create table statement
     **/
    function GenerateCreateTableStmt($objStruct)
    {
        $strTableBody = $this->GenerateCreateTableBodyStmt($objStruct);

        $strSQL = "if not exists (select 1 from sysobjects where id=object_id('".$objStruct->GetTableName()."') and type='U')\n";
        $strSQL .= "begin\n";
        $strSQL .= str_repeat(' ', 4)."create table ".$objStruct->GetTableName()." (\n";
        $strSQL .= $strTableBody;
        $strSQL .= str_repeat(' ', 4).")\n";
        $strSQL .= "end;";

        return $strSQL;
    }

    /**
     * Generate body of create table statement
     **/
    private function GenerateCreateTableBodyStmt($objStruct)
    {
        $strBody = "";
        $arrLine = array();

        // Append column names one by one
        $intLen = 0;
        $objStruct->ResetIterator();
        while ($arrCol = $objStruct->GetNextCol()) {
            $strLine = str_repeat(' ', 8).$arrCol['name'];
            $arrLine[] = $strLine;
            if (strlen($strLine) > $intLen) {
                $intLen = strlen($strLine);
            }
        }

        // Append column types one by one
        $intLen += 4;
        $intNewLen = 0;
        $intIdx = 0;
        $objStruct->ResetIterator();
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
        $objStruct->ResetIterator();
        while ($arrCol = $objStruct->GetNextCol()) {
            $strLine = $arrLine[$intIdx];

            if (false === $arrCol['nullable']) {
                $strLine .= str_repeat(' ', $intLen-strlen($strLine)).'not null';
                if (false !== $arrCol['default']) {
                    if (self::IsCharCol($arrCol['type'])) {
                        $strLine .= " default ".$arrCol['default']."";
                    } else if (self::IsNumCol($arrCol['type'])) {
                        $strLine .= " default ".(is_numeric($arrCol['default']) ? $arrCol['default'] : 0);
                    }else {
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
        $strBody = substr($strBody, 0, strlen($strBody)-2)."\n";

        $boolHasCnst = false;
        // Add primary constraint
        if ($objStruct->HasPrimaryConstraint()) {
            $objCnst = $objStruct->GetPrimaryConstraint();
            $strBody .= str_repeat(' ', 8)."constraint ".$objCnst->GetName()." primary key (".implode(',', $objCnst->GetColumns())."),\n";
            $boolHasCnst = true;
        }

        // Add clustered unique constraints
        $arrCluster = $objStruct->GetClusteredUniqueConstraints();
        if (count($arrCluster) > 0) {
            foreach ($arrCluster as $objCnst) {
                $strBody .= str_repeat(' ', 8)."constraint ".$objCnst->GetName();
                $strBody .= " unique (".implode(',', $objCnst->GetColumns())."),\n";
            }
            $boolHasCnst = true;
        }

        if ($boolHasCnst) {
            $strBody = substr($strBody, 0, strlen($strBody)-2)."\n";
        }

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
            return "convert(varchar(32), getdate(), 120)";
        }

        $strColumnAlias = false === $strColumnAlias ? $strColumnName : $strColumnAlias;
        return "convert(varchar(32), $strTableAlias.$strColumnName, 120) as $strColumnAlias";
    }

    /**
     * Generate partition of insert statement for date and time fields
     *
     * @param string Column value
     * @return string
     **/
    function GetInsertDateStr($strVal)
    {
        return "convert(datetime, '$strVal', 120)";
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
        return preg_replace('/^select([ ]top[ ][0-9]+)?/', "select top $intLimit", $strSQL);
    }
}
?>
