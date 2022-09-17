#!/usr/bin/bash
FORMAT="${1:-pdf}"
VIEWER="${2:-browse}"
SOURCE="${3:-diagram}"
php erd.php > "$SOURCE.dot"
dot "-T$FORMAT" -o "$SOURCE.$FORMAT" "$SOURCE.dot"
$VIEWER "$SOURCE.$FORMAT"
