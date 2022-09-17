<?php 

require "config.php";  // <-- $DB_* vars

$tables = [];
$foreignKeys = [];
$grouped = [];
$ungrouped = [];

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

function findGroup(string $t1, string $t2): string {
    $g1 = explode('_', $t1);
    $g2 = explode('_', $t2);
    $min = min(count($g1), count($g2));
    $parts = [];
    if ($min > 0) {
        foreach (range(0, $min - 1) as $i) {
            if ($g1[$i] === $g2[$i]) {
                $parts[] = $g1[$i];
            } else {
                break;
            }
        }
    }
    $group = join('_', $parts);
    return $group;
}

while ($row = $result->fetch_assoc()) {

	$t1 = $row['TABLE_NAME'];
	$c1 = $row['COLUMN_NAME'];
	$t2 = $row['REFERENCED_TABLE_NAME'];
	$c2 = $row['REFERENCED_COLUMN_NAME'];

    //  Tables

	if (!isset($tables[$t1])) {
		$tables[$t1] = [$c1];
	} elseif (!in_array($c1, $tables[$t1])) {
		$tables[$t1][] = $c1;
	}
	if (!isset($tables[$t2])) {
		$tables[$t2] = [$c2];
	} elseif (!in_array($c2, $tables[$t2])) {
		$tables[$t2][] = $c2;
	}

    //  Foreign Keys, grouped and ungrouped

    if ($t1 == $t2) continue;

	$foreignKey = "$t1:$c1 -> $t2:$c2";
	$foreignKeyReverse = "$t2:$c2 -> $t1:$c1";

    $group = findGroup($t1, $t2);

    if ($group === '') {
        if (
            !in_array($foreignKey, $ungrouped) && 
            !in_array($foreignKeyReverse, $ungrouped)
        ) {
            $ungrouped[] = $foreignKey;
        }
    } else {
        if (!isset($grouped[$group])) {
            $grouped[$group] = [$foreignKey];
        } else {
            if (
                !in_array($foreignKey, $grouped[$group]) &&
                !in_array($foreignKeyReverse, $grouped[$group])
            ) {
                $grouped[$group][] = $foreignKey;
            }
        }
    }

}

?>
digraph Database_Diagram {

	graph [
        pad="1.5"
        ranksep="0.5";
        nodesep="0.5";
        overlap=true;
        splines=false;
        rankdir="LR";
	]

	node [
		fontname="Helvetica,Arial,sans-serif";
		shape=record;
		style=filled;
		fillcolor=gray95;
	]

    edge [
        dir=both;
    ]

<?php foreach ($tables as $tableName => $table): ?>
    <?= $tableName ?> [
		shape=plain
		label=<<table border="0" cellborder="1" cellspacing="0" cellpadding="4">
			<tr>
                <td><b><?= $tableName ?></b></td>
			</tr>
			<tr>
				<td>
					<table border="0" cellborder="0" cellspacing="0" >
	<?php foreach ($table as $columnName): ?>
						<tr>
                            <td port="<?= $columnName ?>" align="left"><?= $columnName ?></td>
						</tr>
	<?php endforeach; ?>
					</table>
				</td>
			</tr>
		</table>>
	]
<?php endforeach; ?>

<?php foreach ($grouped as $groupName => $foreignKeys): ?>
    subgraph cluster_<?= $groupName ?> {
        rankdir="LR";
        pad="1.5";
        color=black;
        label="<?= $groupName ?>";
    <?php foreach ($foreignKeys as $foreignKey): ?>
        <?= $foreignKey ?>;
    <?php endforeach; ?>
    }
<?php endforeach; ?>

<?php foreach ($ungrouped as $foreignKey): ?>
    <?= $foreignKey ?>;
<?php endforeach; ?>

}
