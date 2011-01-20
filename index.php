<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
        <title>Dumperor</title>
    </head>
    <body>
        <?php
        $cfgFiles = array();
        $dir = @opendir('conf');

        if ($dir) {
            while (($file = readdir($dir)) !== false) {
                if ('.ini' == strrchr($file, '.')) {
                    $cfgFiles[] = $file;
                }
            }
            closedir($dir);
        }

        if (count($cfgFiles)>0) {
            echo '<h1>Available configurations:</h1>';
            echo '<ol>';
            foreach ($cfgFiles as $file) {
                echo "<li><a href=\"dump.php?c=".urlencode(basename($file, '.ini'))."\" target=\"_blank\">$file</a></li>";
            }
            echo '</ol>';
        } else {
            echo '<h1>No configurations found !</h1>';
        }
        ?>
    </body>
</html>
