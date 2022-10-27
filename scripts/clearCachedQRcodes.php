
<?php
// PHP program to delete all
// file from a folder
$fileToDelete = "";

if (!isset($argv[1])) {
    echo "Error: Insufficient args". PHP_EOL;
    echo "Use --help for help" . PHP_EOL;
    exit(1);
}

if (isset($argv[2])) {
    echo "Error: too many args". PHP_EOL;
    echo "Use --help for help" . PHP_EOL;
    exit(1);
}

if (in_array("--help", $argv) || in_array("-h", $argv)) {
    echo "Command line script to delete cached QRCodes " . PHP_EOL;
    echo PHP_EOL;
    echo "Usage: [options] <args>" . PHP_EOL;
    echo PHP_EOL;
    echo "Options:" . PHP_EOL;
    echo "\t --help or -h \t\t Output help text" . PHP_EOL;
    echo "\t --all or -a \t\t Delete all cached QRCodes" . PHP_EOL;
    echo PHP_EOL;
    echo "Arguments:" . PHP_EOL;
    echo "\t url \t\t Short URL to delete ex: https://go.unl.edu/bowling" . PHP_EOL;
    echo "\t\t\t Needs to be whole URL since a hashed version is the files name" . PHP_EOL;
    exit(0);
}

if (in_array("--all", $argv) || in_array("-a", $argv)) {
    echo "Are you sure you want to delete all cached files?  Type 'yes' to continue: ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) != 'yes') {
        echo "ABORTING!\n";
        exit;
    }
    fclose($handle);
    $fileToDelete = "*";
} else {
    $fileToDelete = hash("sha512", $argv[1]);
}

// Folder path to be flushed
$folder_path = dirname(__DIR__) . '/data/qr/cache';

// List of name of files inside
// specified folder
$files = glob($folder_path . '/' . $fileToDelete . '.{png,svg}', GLOB_BRACE);

if (count($files) == 0) {
    echo "Error: No files to remove" . PHP_EOL;
    exit(1);
    
} else {
    echo "Removing " . count($files) . " files" . PHP_EOL;
}

// Deleting all the files in the list
foreach ($files as $file) {
    
    if (is_file($file)) {
        // Delete the given file
        unlink($file);
    }
}

echo "Complete!" . PHP_EOL;
