<?php
/**
 * Dumperor
 *
 * @author Li Dong <lenin.lee@gmail.com>
 * @version 0.1a
 * @license http://www.opensource.org/licenses/bsd-license.php
 **/
require_once 'wrappers/pdowrapper.php';
require_once 'dumpers/mssqldumper.php';
require_once 'dumpers/mysqldumper.php';
require_once 'dumpers/oracledumper.php';
require_once 'conf/settingsparser.php';

set_time_limit(0);

$objCfg = new SettingsParser();
try {
    $objCfg->ParseFile('conf/'.urldecode($_GET['c']).'.ini');
} catch (Exception $e) {
    die($e->GetMessage());
}

// Get settings
$strDBLogin = $objCfg->get('db', 'login');
$strDBPass = $objCfg->get('db', 'password');
$strDBHost = $objCfg->get('db', 'host');
$strDBName = $objCfg->get('db', 'name');
$strDBType = $objCfg->get('db', 'type');
$arrCond = $objCfg->get('cond');
$arrEscTbl = $objCfg->get('exclusive', 'tables');
$arrIncTbl = $objCfg->get('inclusive', 'tables');
$arrCommonEscCol = $objCfg->get('exclusive', 'columns');
$arrFake = $objCfg->get('fake');
$intLimit = $objCfg->get('output', 'limit');
$outputDir = $objCfg->get('output', 'dir');
$bShowTbl = $objCfg->get('output', 'showtable');
$bShowCreate = $objCfg->get('output', 'showcreate');
$commTypes = $objCfg->get('common_type_name');
$commStructs = $objCfg->get('common_column_structure');

// Check environment
if (!isset($_GET['action'])) {
    die('Error: Action missing.');
}
if (!file_exists($outputDir) && !mkdir($outputDir)) {
    die('Error: Cannot mkdir '.$outputDir);
}

// Prepare variables
$outputFile = null === $outputDir ? null : $outputDir.DIRECTORY_SEPARATOR.urldecode($_GET['c']).'_'.$_GET['action'];
switch ($_GET['action']) {
    case 'difftable':
        $outputFile .= '.txt';
        break;
    case 'diffdata':
        $outputFile .= '.txt';
        break;
    case 'dumptable':
        $outputFile .= '.sql';
        break;
    case 'dumpdata':
        $outputFile .= '.sql';
        break;
    default:
        die('Error: Unknown action.');
}

// Connect to database
$db = new PDOWrapper($strDBLogin, $strDBPass, $strDBHost, $strDBName, $strDBType);
try {
    $db->connect();
} catch (Exception $e) {
    die($e->GetMessage());
}

// Get dumper
$dumper = GetDumper($db, $objCfg);
$dumper->SetCommonCond($arrCond);
$dumper->AddEscapeTables($arrEscTbl);
$dumper->AddIncludeTables($arrIncTbl);
//$dumper->SetEscapeColumns($arrEscCol);
$dumper->SetCommonEscCol($arrCommonEscCol);
$dumper->SetFakeData($arrFake);
$dumper->SetLimit($intLimit);
$dumper->SetCommonTypeNames($commTypes);
$dumper->SetCommonColumnStructs($commStructs);

StartHTMLPage($objCfg);

$arrTbl = $dumper->DumpTableNames();
sort($arrTbl);
foreach ($arrTbl as $strTbl) {
    switch ($_GET['action']) {
        case 'difftable':
            $struct = $dumper->DumpTableStructure($strTbl);
            echo '<pre>';
            $dumper->OutputTableStructure($struct, $outputFile);
            $dumper->Output("\n", $outputFile);
            echo '</pre>';
            break;
        case 'diffdata':
            $lines = $dumper->DumpDiffableData($strTbl, $outputFile, true);
            echo <<<HTML
<p>
$strTbl:<br>
$lines lines of data dumped.
</p>
HTML;
            break;
        case 'dumptable':
            $struct = $dumper->DumpTableStructure($strTbl);
            if ($bShowTbl) {
                $struct->OutputHTML();
            }
            $strSQL = $dumper->GenerateCreateTableStmt($struct);
            echo '<pre>';
            $dumper->Output($strSQL, $outputFile, $bShowCreate);
            $dumper->Output("\n\n", $outputFile, $bShowCreate);
            echo '</pre>';
            break;
        case 'dumpdata':
            $lines = $dumper->DumpDataForTable($strTbl, $outputFile);
            echo <<<HTML
<p>
$strTbl:<br>
$lines lines of data dumped.
</p>
HTML;
            break;
    }
}

StopHTMLPage($objCfg);

try {
    $db->close();
} catch (Exception $e) {
    die($e->GetMessage());
}

die('Job done !');

/**
 * Get a dumper instance
 **/
function GetDumper($conn, $objCfg)
{
    $dumper = false;

    switch ($objCfg->get('db', 'type')) {
        case 'sqlsrv':
        case 'dblib':
        case 'mssql':
            $dumper = new MSSQLDumper($conn, $objCfg->get('db', 'name'));
            break;
        case 'mysql':
            $dumper = new MySQLDumper($conn, $objCfg->get('db', 'name'));
            break;
        case 'oracle':
        case 'oci':
            $dumper = new OracleDumper($conn, $objCfg->get('db', 'name'));
            break;
        default:
            break;
    }

    return $dumper;
}

function StartHTMLPage($objCfg)
{
?>
<html>
<head>
<title>Dumperor: <?php echo $objCfg->get('db', 'name'); ?></title>
<meta http-equiv="Content-Type" content="text/html;charset=<?php echo $objCfg->get('output', 'charset'); ?>" />
</head>
<body>
<?php
}

function StopHTMLPage($objCfg)
{
?>
</body>
</html>
<?php
}
?>
