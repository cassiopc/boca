<?php
// Run inside the BOCA web image; render both report charts with PHP/GD.
chdir('/var/www/boca/src/libchart');
require 'libchart.php';
foreach (['PieChart', 'VerticalChart'] as $type) {
    $chart = new $type(400, 250);
    $chart->addPoint(new Point('A', 5));
    $chart->addPoint(new Point('B', 3));
    if ($type === 'PieChart') $chart->order = true;
    $path = sys_get_temp_dir() . '/boca-chart-' . strtolower($type) . '.png';
    $chart->render($path);
    if (!is_file($path) || filesize($path) < 1000 || getimagesize($path)[2] !== IMAGETYPE_PNG)
        throw new RuntimeException('Gráfico inválido: ' . $type);
    unlink($path);
    echo $type . " OK\n";
}
