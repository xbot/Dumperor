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
    var $columns;                   // array, columns
    var $objPrimaryConstraint;      // object, instance of TableConstraint standing for primary key
    var $arrUniqueCnst;             // array, instances of TableConstraint standing for unique constraint
    var $arrNonPrecType;            // array, data types which don't need length and precision
    
    function __construct($strTableName)
    {
        $this->strTableName = $strTableName;
        $this->columns = array();
        $this->objPrimaryConstraint = false;
        $this->arrUniqueCnst = array();
        $this->arrNonPrecType = array('SMALLINT', 'INT', 'BIT', 'BOOL', 'TINYINT', 'IMAGE', 'DATE', 'DATETIME', 'TIME', 'TIMESTAMP', 'TEXT', 'LONG', 'BLOB', 'CLOB');
    }

    /**
     * Reset iterator index
     **/
    function Reset()
    {
        reset($this->columns);
    }

    /**
     * Sanitize myself
     * Reset lengths and precisions of data types in arrNonPrecType to 0 
     **/
    function Sanitize()
    {
        foreach ($this->columns as $colName=>$colDef) {
            //Reset lengths and precisions of data types in arrNonPrecType to 0 
            if ($this->IsNonPrecType($colDef['type'])) {
                $this->columns[$colName]['length'] = 0;
                $this->columns[$colName]['precision'] = 0;
            }

            if (!is_bool($colDef['nullable'])) {
                if (in_array(strtoupper($colDef['nullable'].''), array('Y', 'YES', '1'))) {
                    $this->columns[$colName]['nullable'] = true;
                } else if (in_array(strtoupper($colDef['nullable'].''), array('N', 'NO', '0'))) {
                    $this->columns[$colName]['nullable'] = false;
                }
            }

            // Unify defaults
            if (!is_bool($colDef['default'])) {
                if (strlen(trim($colDef['default'])) == 0) {
                    $this->columns[$colName]['default'] = false;
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
        return next($this->columns);
    }

    /**
     * Append a column defination to the structure
     *
     * @param array Column defination
     * @return false
     **/
    public function AppendColumn($col)
    {
        $this->columns[strtolower($col['name'])] = $col;
        $this->Sanitize();
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
                    if (strtoupper($strColName) === strtoupper($strColumn)) {
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
     * Getter for $this->columns
     **/
    public function GetColumns()
    {
        return $this->columns;
    }

    /**
     * Generate an HTML table to display the table structure
     **/
    function OutputHTML()
    {
        $strColumns = "";

        foreach ($this->columns as $colName=>$colDef) {
            $strName = $colDef['name'];
            $strType = $colDef['type'];
            $strLength = $colDef['length'];
            $strPrecision = $colDef['precision'];
            $boolNullable = $colDef['nullable'];
            $strDefault = $colDef['default'];

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

    /**
     * Generate a template string for sprintf() to format the value of a given column
     *
     * @todo complete this function
     **/
    public function GetColumnTemplate($colName)
    {
        $colName = strtolower($colName);
        if (array_key_exists($colName, $this->columns)) {
            $tmpl = "$colName: %s";
            return $tmpl;
        }
        return "$colName: %s";
    }

    /**
     * Generate a template string for sprintf() to format the output of the table
     *
     * @param array Escaped columns, default null
     * @return none
     **/
    public function GetTemplate($escCols=null)
    {
        $colTmpls = array();
        $columns = $this->GetColumns();
        ksort($columns);
        foreach ($columns as $colName=>$colDef) {
            if (is_array($escCols) && in_array($colName, $escCols)) {
                continue;
            }
            $colTmpls[] = $this->GetColumnTemplate($colName);
        }
        return implode(',', $colTmpls);
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
