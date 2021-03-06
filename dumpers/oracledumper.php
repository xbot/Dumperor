<?php
/**
 * @author Li Dong <lenin.lee@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php
 **/
include_once 'genericdumper.php';

/**
 * oracle dumper
 **/
class OracleDumper extends GenericDumper
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
            $strEscapeCond = " and table_name not in ('".strtoupper(implode("','", $this->arrEscapeTable))."')";
        }

        if (is_array($this->arrIncludeTable) && count($this->arrIncludeTable)>0) {
            $strIncludeCond = " and table_name in ('".strtoupper(implode("','", $this->arrIncludeTable))."')";
        }

        $sql = "select table_name from user_tables where temporary='N'";
        $sql .= $strEscapeCond;
        $sql .= $strIncludeCond;
        $sql .= $strOrderCond;

        $rs = $this->objDB->query($sql);
        if ($rs) {
            while (($arrRow = $this->objDB->read($rs)) !== false) {
                $arrTableName[] = strtolower($arrRow['TABLE_NAME']);
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
        $strTableName = strtoupper($strTableName);

        $arrField = array();
        $strOrderCond = " order by col.column_id";
        //$strOrderCond = " order by col.column_name";

        $sql = "select col.column_name as fld_name,col.data_type as fld_type,nvl(col.data_precision,0) as fld_length,nvl(col.data_scale,0) as fld_precision,nvl(col.char_col_decl_length,0) as fld_char_length,col.nullable as fld_nullable,col.data_default as fld_default";
        $sql .= " from user_tab_columns col";
        $sql .= " where col.table_name='$strTableName'";
        $sql .= $strOrderCond;
        //echo htmlspecialchars($sql);die;

        $rs = $this->objDB->query($sql);
        if ($rs) {
            while (($arrRow = $this->objDB->read($rs)) !== false) {
                $arrName[] = $arrRow['FLD_NAME'];
                $strType = $arrRow['FLD_TYPE'];
                $arrType[] = $strType;
                if (self::IsNumCol($strType)) {
                    $arrLength[] = $arrRow['FLD_LENGTH'];
                    $arrPrecision[] = $arrRow['FLD_PRECISION'];
                } else if (self::IsCharCol($strType)) {
                    $arrLength[] = $arrRow['FLD_CHAR_LENGTH'];
                    $arrPrecision[] = 0;
                } else {
                    $arrLength[] = 0;
                    $arrPrecision[] = 0;
                }
                $arrNullable[] = $arrRow['FLD_NULLABLE'];
                $arrDefault[] = $arrRow['FLD_DEFAULT'];
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
        $strTablespace = '';

        $sql = "select cu.constraint_name as name,cu.column_name,idx.tablespace_name 
            from user_cons_columns cu,user_constraints au,user_indexes idx 
            where cu.constraint_name=au.constraint_name and au.constraint_type='P' 
            and idx.index_name=cu.constraint_name 
            and au.table_name=upper('$strTableName')";
        //echo $sql;

        $rs = $this->objDB->query($sql);
        if ($rs) {
            while (($arrRow = $this->objDB->read($rs)) !== false) {
                $strName = $arrRow['NAME'];
                $arrCol[] = $arrRow['COLUMN_NAME'];
                $strTablespace = $arrRow['TABLESPACE_NAME'];
            }
            $this->objDB->free($rs);
            $objCnst = new TableConstraint($strName, $strType, $arrCol);
        }

        if (0 < count($arrCol)) {
            $objCnst = new TableConstraint($strName, $strType, $arrCol);
            $objCnst->SetTablespace($strTablespace);
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

        $sql = "select cu.constraint_name as name,cu.column_name 
            from user_cons_columns cu,user_constraints au 
            where cu.constraint_name=au.constraint_name and au.constraint_type='U' 
            and au.table_name=upper('$strTableName') 
            order by cu.constraint_name,cu.position";

        $rs = $this->objDB->query($sql);
        if ($rs) {
            while (($arrRow = $this->objDB->read($rs)) !== false) {
                $arrCol = array();

                $strName = $arrRow['NAME'];
                $strColumn = $arrRow['COLUMN_NAME'];
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
     * This method overrides the parent one, add function to populate lob columns in oracle
     *
     * @param string Column type
     * @param mixed Column value
     * @return string
     **/
    protected function GenerateInsertParamCol($strType, $mixVal)
    {
        $strParam = '';

        if (self::IsLobCol($strType)) {
            //$strParam = '"'.base64_decode(stream_get_contents($mixVal)).'"';
            $strParam = '"\'\'"';
        } else {
            $strParam = parent::GenerateInsertParamCol($strType, $mixVal);
        }

        return $strParam;
    }

    /**
     * Return the batch seperator of Oracle
     *
     * @return string
     **/
    function GetBatchSeperator()
    {
        return '/';
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

        $strSQL = "declare v_cnt number;\n";
        $strSQL .= "begin\n";
        $strSQL .= str_repeat(' ', 4)."v_cnt := f_tbl_exists('".strtoupper($objStruct->GetTableName())."');\n";
        $strSQL .= str_repeat(' ', 4)."if v_cnt=0 then\n";
        $strSQL .= str_repeat(' ', 8)."execute immediate 'create table ".$objStruct->GetTableName()." (\n";
        $strSQL .= $strTableBody;
        $strSQL .= str_repeat(' ', 8).")\n";
        $strSQL .= str_repeat(' ', 8)."';\n";
        $strSQL .= str_repeat(' ', 4)."end if;\n";
        $strSQL .= "end;";

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
            $strLine = str_repeat(' ', 12).$arrCol['name'];
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
                if (false !== $arrCol['default']) {
                    $strLine .= str_repeat(' ', $intLen-strlen($strLine));
                    if (self::IsCharCol($arrCol['type'])) {
                        $strLine .= "default '".trim($arrCol['default'])."'";
                    } else if (self::IsNumCol($arrCol['type'])) {
                        $strLine .= "default ".(is_numeric($arrCol['default']) ? $arrCol['default'] : 0);
                    }else {
                        $strLine .= "default ".$arrCol['default'];
                    }
                }
                $strLine .= ' not null';
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
            $strBody .= str_repeat(' ', 12)."constraint ".$objCnst->GetName();
            $strBody .= " primary key (".implode(',', $objCnst->GetColumns()).")";
            $strBody .= " using index tablespace ".$objCnst->GetTablespace().",\n";
        }

        // Add clustered unique constraints
        $arrCluster = $objStruct->GetClusteredUniqueConstraints();
        if (count($arrCluster) > 0) {
            foreach ($arrCluster as $objCnst) {
                $strBody .= str_repeat(' ', 12)."constraint ".$objCnst->GetName();
                $strBody .= " unique (".implode(',', $objCnst->GetColumns())."),\n";
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
            return "to_char(sysdate, 'YYYY-MM-DD HH24:MI:SS')";
        }

        $strColumnAlias = false === $strColumnAlias ? $strColumnName : $strColumnAlias;
        return "to_char($strTableAlias.$strColumnName, 'YYYY-MM-DD HH24:MI:SS') as $strColumnAlias";
    }

    /**
     * Generate partition of insert statement for date and time fields
     *
     * @param string Column value
     * @return string
     **/
    function GetInsertDateStr($strVal)
    {
        return "to_date('$strVal', 'YYYY-MM-DD HH24:MI:SS')";
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
        return strstr($strSQL, 'order by') ? "select * from ($strSQL) where rownum<=$intLimit" : "$strSQL and rownum<=$intLimit";
    }

    function hookColHandler(&$sql, $arr)
    {
        // Oracle PDO driver may raise exception if a lob column is null
        if ($this->IsLobCol($arr['type'])) {
            //$sql .= "nvl(tbl.".$arr['name'].", '0') as ".$arr['name'].",";
            //TODO: fix me
            $sql .= "'' as ".$arr['name'].",";
            return true;
        }
        return false;
    }
}
?>
