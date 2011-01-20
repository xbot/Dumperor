<?php
/**
 * @author Li Dong <lenin.lee@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php
 **/

/**
 * TableStructure
 **/
class TableStructure
{
    var $strTableName;              // string, table name
    var $arrColumnName;             // array, column name
    var $arrColumnType;             // array, column type
    var $arrColumnLength;           // array, column length
    var $arrColumnPrecision;        // array, column precision
    var $arrColumnDefault;          // array, column defaults
    var $arrColumnNullable;         // array, if is nullable
    var $intIterator;               // integer, interator index
    var $objPrimaryConstraint;      // object, instance of TableConstraint standing for primary key
    var $arrUniqueCnst;             // array, instances of TableConstraint standing for unique constraint
    var $arrNonPrecType;            // array, data types which don't need length and precision
    
    function __construct($strTableName)
    {
        $this->strTableName = $strTableName;
        $this->arrColumnName = array();
        $this->arrColumnType = array();
        $this->arrColumnLength = array();
        $this->arrColumnPrecision = array();
        $this->arrColumnDefault = array();
        $this->arrColumnNullable = array();
        $this->objPrimaryConstraint = false;
        $this->arrUniqueCnst = array();
        $this->arrNonPrecType = array('SMALLINT', 'INT', 'BIT', 'BOOL', 'TINYINT', 'IMAGE', 'DATE', 'DATETIME', 'TIME', 'TIMESTAMP', 'TEXT', 'LONG', 'BLOB', 'CLOB');
        $this->intIterator = 0;
    }

    /**
     * Reset iterator index
     **/
    function ResetIterator()
    {
        $this->intIterator = 0;
    }

    /**
     * Sanitize myself
     * Reset lengths and precisions of data types in arrNonPrecType to 0 
     **/
    function Sanitize()
    {
        //Reset lengths and precisions of data types in arrNonPrecType to 0 
        $intCntType = count($this->arrColumnType);
        $intCntLength = count($this->arrColumnLength);
        $intCntPrec = count($this->arrColumnPrecision);
        if ($intCntType == $intCntLength && $intCntLength == $intCntPrec) {
            for ($i = 0; $i < $intCntType; $i++) {
                if ($this->IsNonPrecType($this->arrColumnType[$i])) {
                    $this->arrColumnLength[$i] = 0;
                    $this->arrColumnPrecision[$i] = 0;
                }
            }
        }

        // Unify nullable values
        if (is_array($this->arrColumnNullable)) {
            for ($i = 0; $i < count($this->arrColumnNullable); $i++) {
                $value = $this->arrColumnNullable[$i];
                if (is_bool($value)) {
                    continue;
                }
                if (in_array(strtoupper($value.''), array('Y', 'YES', '1'))) {
                    $this->arrColumnNullable[$i] = true;
                } else if (in_array(strtoupper($value.''), array('N', 'NO', '0'))) {
                    $this->arrColumnNullable[$i] = false;
                }
            }
        }

        // Unify defaults
        if (is_array($this->arrColumnDefault)) {
            for ($i = 0; $i < count($this->arrColumnDefault); $i++) {
                $value = $this->arrColumnDefault[$i];
                if (is_bool($value)) {
                    continue;
                }
                if (strlen(trim($value)) == 0) {
                    $this->arrColumnDefault[$i] = false;
                }
            }
        }
    }

    /**
     * Get info array of next column according to the iterator
     *
     * @return array False if no more column exists
     **/
    function GetNextCol()
    {
        if ($this->intIterator < count($this->arrColumnName)) {
            $arr = array();
            $arr['name'] = $this->arrColumnName[$this->intIterator];
            $arr['type'] = $this->arrColumnType[$this->intIterator];
            $arr['length'] = $this->arrColumnLength[$this->intIterator];
            $arr['precision'] = $this->arrColumnPrecision[$this->intIterator];
            $arr['default'] = $this->arrColumnDefault[$this->intIterator];
            $arr['nullable'] = $this->arrColumnNullable[$this->intIterator];
            $this->intIterator += 1;
            return $arr;
        }

        return false;
    }

    /**
     * Getter of data types that don't need length and precision
     **/
    function GetNonPrecTypes()
    {
        return $this->arrNonPrecType;
    }

    /**
     * Setter of data types that don't need length and precision
     **/
    function SetNonPrecTypes($arrType)
    {
        $this->arrNonPrecType = $arrType;
        $this->Sanitize();
    }

    /**
     * Check if the given data type is one that doesn't need length and precision
     **/
    function IsNonPrecType($strType)
    {
        return in_array(strtoupper($strType), $this->arrNonPrecType);
    }

    /**
     * Getter of table name
     **/
    function GetTableName()
    {
        return $this->strTableName;
    }

    /**
     * Setter of table name
     **/
    function SetTableName($strTableName)
    {
        $this->strTableName = $strTableName;
        $this->Sanitize();
    }

    /**
     * Getter of column names
     **/
    function GetColumnNames()
    {
        return $this->arrColumnName;
    }

    /**
     * Setter of column names
     **/
    function SetColumnNames($arrColumnName)
    {
        $this->arrColumnName = $arrColumnName;
        $this->Sanitize();
    }

    /**
     * Getter of column Types
     **/
    function GetColumnTypes()
    {
        return $this->arrColumnType;
    }

    /**
     * Setter of column Types
     **/
    function SetColumnTypes($arrColumnType)
    {
        $this->arrColumnType = $arrColumnType;
        $this->Sanitize();
    }

    /**
     * Getter of column Lengths
     **/
    function GetColumnLengths()
    {
        return $this->arrColumnLength;
    }

    /**
     * Setter of column Lengths
     **/
    function SetColumnLengths($arrColumnLength)
    {
        $this->arrColumnLength = $arrColumnLength;
        $this->Sanitize();
    }

    /**
     * Getter of column precisions
     **/
    function GetColumnPrecisions()
    {
        return $this->arrColumnPrecision;
    }

    /**
     * Setter of column precisions
     **/
    function SetColumnPrecisions($arrColumnPrecision)
    {
        $this->arrColumnPrecision = $arrColumnPrecision;
        $this->Sanitize();
    }

    /**
     * Getter of column Nullables
     **/
    function GetColumnNullables()
    {
        return $this->arrColumnNullable;
    }

    /**
     * Setter of column Nullables
     **/
    function SetColumnNullables($arrColumnNullable)
    {
        $this->arrColumnNullable = $arrColumnNullable;
        $this->Sanitize();
    }

    /**
     * Getter of column Defaults
     **/
    function GetColumnDefaults()
    {
        return $this->arrColumnDefault;
    }

    /**
     * Setter of column Defaults
     **/
    function SetColumnDefaults($arrColumnDefault)
    {
        $this->arrColumnDefault = $arrColumnDefault;
        $this->Sanitize();
    }

    /**
     * Check if the primary key constraint exists
     **/
    function HasPrimaryConstraint()
    {
        return is_object($this->objPrimaryConstraint) && 'TABLECONSTRAINT' == strtoupper(get_class($this->objPrimaryConstraint));
    }

    /**
     * Getter of primary key constraint
     **/
    function GetPrimaryConstraint()
    {
        return $this->objPrimaryConstraint;
    }

    /**
     * Setter of primary key constraint
     **/
    function SetPrimaryConstraint($objPrimaryConstraint)
    {
        $this->objPrimaryConstraint = $objPrimaryConstraint;
        $this->Sanitize();
    }

    /**
     * Getter of unique constraints
     **/
    function GetUniqueConstraints()
    {
        return $this->arrUniqueCnst;
    }

    /**
     * Setter of unique constraints
     **/
    function SetUniqueConstraints($arrUniqueCnst)
    {
        $this->arrUniqueCnst = $arrUniqueCnst;
        $this->Sanitize();
    }

    /**
     * Check if the given column is primary key
     **/
    function IsPrimaryKey($strColumnName)
    {
        if (is_object($this->objPrimaryConstraint)) {
            $arr = $this->objPrimaryConstraint->GetColumns();
            for ($i = 0; $i < count($arr); $i++) {
                if (strtoupper($strColumnName) == strtoupper($arr[$i])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if the given column is unique
     **/
    function IsUnique($strColumnName)
    {
        $objCnst = $this->GetUniqueConstraintByColumn($strColumnName);

        return is_object($objCnst);
    }

    /**
     * Check if the given column is belong to a single unique constraint
     **/
    function IsSingleUniqueColumn($strColumnName)
    {
        $objCnst = $this->GetUniqueConstraintByColumn($strColumnName);

        return is_object($objCnst) && !$objCnst->IsCluster();
    }

    /**
     * Check if the given column is belong to a clustered unique constraint
     **/
    function IsClusteredUnique($strColumnName)
    {
        $objCnst = $this->GetUniqueConstraintByColumn($strColumnName);

        return is_object($objCnst) && $objCnst->IsCluster();
    }

    /**
     * Get the unique constraint object for the given column
     *
     * @param string
     * @return object
     **/
    function GetUniqueConstraintByColumn($strColName)
    {
        if (is_array($this->arrUniqueCnst)) {
            foreach ($this->arrUniqueCnst as $objCnst) {
                $arrColumn = $objCnst->GetColumns();
                foreach ($arrColumn as $strColumn) {
                    if (strtoupper($strColName) == strtoupper($strColumn)) {
                        return $objCnst;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get all clustered unique constraints
     *
     * @return array
     **/
    function GetClusteredUniqueConstraints()
    {
        $arr = array();

        foreach ($this->arrUniqueCnst as $objCnst) {
            if ($objCnst->IsCluster()) {
                $arr[] = $objCnst;
            }
        }

        return $arr;
    }

    /**
     * Generate an HTML table to display the table structure
     **/
    function OutputHTML()
    {
        $strColumns = "";

        for ($i = 0; $i < count($this->arrColumnName); $i++) {
            $strName = $this->arrColumnName[$i];
            $strType = $this->arrColumnType[$i];
            $strLength = $this->arrColumnLength[$i];
            $strPrecision = $this->arrColumnPrecision[$i];
            $boolNullable = $this->arrColumnNullable[$i];
            $strDefault = $this->arrColumnDefault[$i];

            $strNullable = $boolNullable === true ? 'TRUE' : ($boolNullable === false ? 'FALSE' : gettype($boolNullable)."($boolNullable)");
            $strDefault = strlen(trim($strDefault)) > 0 ? $strDefault : '&nbsp;';
            $objUnique = $this->GetUniqueConstraintByColumn($strName);

            $strColumns .= "
                <tr>
                    <td>$strName</td>
                    <td>$strType</td>
                    <td>$strLength</td>
                    <td>$strPrecision</td>
                    <td>$strNullable</td>
                    <td>$strDefault</td>
                    <td>".($this->IsPrimaryKey($strName) ? $this->objPrimaryConstraint->GetName() : '&nbsp;')."</td>
                    <td>".(is_object($objUnique) ? $objUnique->GetName() : '&nbsp;')."</td>
                </tr>
            ";
        }

        $strHTML = "
            <table border=1>
                <tr>
                    <th colspan=\"8\">$this->strTableName</th>
                </tr>
                <tr>
                    <th>Column</th>
                    <th>Type</th>
                    <th>Length</th>
                    <th>Precision</th>
                    <th>Nullable</th>
                    <th>Default</th>
                    <th>Primary Key</th>
                    <th>Unique</th>
                </tr>
                $strColumns
            </table>
        ";

        echo $strHTML;
    }
}

/**
 * Table Constraint
 **/
class TableConstraint
{
    var $strName;                   // string, Constraint name
    var $strType;                   // string, Constraint type, currently available ones: PRIMARY, UNIQUE
    var $arrColumn;                 // array, column names
    var $strTablespace;             // string, tablespace name, used only by oracle

    function __construct($strName, $strType, $arrColumn)
    {
        $this->strName = $strName;
        $this->strType = $strType;
        $this->arrColumn = $arrColumn;
        $this->strTablespace = '';
    }

    /**
     * Getter of constraint name
     **/
    function GetName()
    {
        return $this->strName;
    }

    /**
     * Setter of constraint name
     **/
    function SetName($strName)
    {
        $this->strName = $strName;
    }

    /**
     * Getter of constraint type
     **/
    function GetType()
    {
        return $this->strType;
    }

    /**
     * Setter of constraint type
     **/
    function SetType($strType)
    {
        return $this->strType = $strType;
    }

    /**
     * Getter of columns
     **/
    function GetColumns()
    {
        return $this->arrColumn;
    }

    /**
     * Setter of columns
     **/
    function SetColumns($arrColumn)
    {
        $this->arrColumn = $arrColumn;
    }

    /**
     * Append a column
     **/
    function AppendColumn($strColumnName)
    {
        if (in_array($strColumnName, $this->arrColumn)) {
            return;
        }

        $this->arrColumn[] = $strColumnName;
    }

    /**
     * Getter of tablespace
     **/
    function GetTablespace()
    {
        return $this->strTablespace;
    }

    /**
     * Setter of tablespace
     **/
    function SetTablespace($strTablespace)
    {
        $this->strTablespace = $strTablespace;
    }

    /**
     * Check if the constraint containts only one column
     **/
    function IsCluster()
    {
        return count($this->GetColumns()) > 1;
    }
}
?>
