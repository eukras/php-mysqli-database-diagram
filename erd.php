<?php 

require "config.php";  // <-- $DB_* vars

$tables = [];
$foreignKeys = [];

$sql = <<<SQL
SELECT
	TABLE_NAME,
	COLUMN_NAME,
	REFERENCED_TABLE_NAME,
	REFERENCED_COLUMN_NAME
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
        REFERENCED_TABLE_NAME IS NOT NULL
    AND REFERENCED_COLUMN_NAME IS NOT NULL
ORDER BY 
    TABLE_NAME,
    COLUMN_NAME
SQL;

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$result = $mysqli->query($sql);

while ($row = $result->fetch_assoc()) {
	$t1 = $row['TABLE_NAME'];
	$c1 = $row['COLUMN_NAME'];
	$t2 = $row['REFERENCED_TABLE_NAME'];
	$c2 = $row['REFERENCED_COLUMN_NAME'];
	if (!isset($tables[$t1])) {
		$tables[$t1] = [$c1];
	} else {
		$tables[$t1][] = $c1;
	}
	if (!isset($tables[$t2])) {
		$tables[$t2] = [$c2];
	} else {
		$tables[$t2][] = $c2;
	}
	$foreignKeys = ["$t1:$c1 -> $t2:$c2"];
}

?>

digraph Database_Diagram {

	graph [
	]

	node [
		fontname="Helvetica,Arial,sans-serif"
		shape=record
		style=filled
		fillcolor=gray95
	]

<?php foreach ($tables as $tableName => $table): ?>
	edge [dir=back arrowtail=empty]
    <?= $tableName ?> [
		shape=plain
		label=<<table border="0" cellborder="1" cellspacing="0" cellpadding="4">
			<tr>
			<td> <b><?= $tableName ?></b> </td>
			</tr>
			<tr>
				<td>
					<table border="0" cellborder="0" cellspacing="0" >
	<?php foreach ($table as $columnName): ?>
						<tr>
						<td port="<?= $columnName ?>" align="left">
								<?= $columnName ?>
							</td>
						</tr>
	<?php endforeach; ?>
					</table>
				</td>
			</tr>
		</table>>
	]
<?php endforeach; ?>

<?php foreach ($foreignKeys as $key): ?>
	<?= $key ?>;
<?php endforeach; ?>

}
