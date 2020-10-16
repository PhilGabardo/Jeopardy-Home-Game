<?php


require('../vendor/autoload.php');


use \Symfony\Component\HttpFoundation\Request;


$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app->get('/jeopardy', function(Request $request) use($app) {
	if (($_SERVER['HTTP_HOST'] !== 'localhost:8080')) {
		return '';
	}
	$app->register(new Csanquer\Silex\PdoServiceProvider\Provider\PDOServiceProvider('pdo'),
		array(
			'pdo.server' => array(
				'driver'   => 'pgsql',
				'user' => 'pg',
				'password' => 'pgpass',
				'host' => '127.0.0.1',
				'port' => 5432,
				'dbname' => 'pgdb'
			)
		)
	);
	$categories = [];
	for ($i = 0; $i < 6; $i++) {
		$category_offset = random_int(0, 39659);
		$st = $app['pdo']->prepare("SELECT distinct(category) FROM jeopardy_questions OFFSET $category_offset LIMIT 1");
		$st->execute();
		$categories[] = $st->fetch(PDO::FETCH_ASSOC)['category'];
	}
	$questions_by_category = [];
	foreach ($categories as $category) {
		$escaped_category = $app['pdo']->quote($category);
		$st = $app['pdo']->prepare("SELECT year, question, answer FROM jeopardy_questions where category = $escaped_category");
		$st->execute();
		$question_candidates = $st->fetchAll(PDO::FETCH_ASSOC);
		shuffle($question_candidates);
		$questions_by_category[$category] = array_slice($question_candidates, 0, 6);
	}
	$daily_double_row = random_int(0, 4);
	$daily_double_col = random_int(0, 5);
	$questions_by_category[$categories[$daily_double_col]][$daily_double_row]['double'] = true;
	$clues = [];
	$is_double = (bool)$request->get('double');
	$multiplier = $is_double ? 400 : 200;
	for ($row = 0; $row < 5; $row++) {
		$clues[$row + 1] = [];
		foreach ($categories as $category) {
			$questions_by_category[$category][$row]['double'] = $questions_by_category[$category][$row]['double'] ?? false;
			$questions_by_category[$category][$row]['question'] = preg_replace('/^\(.*\)/', '', $questions_by_category[$category][$row]['question']);
			$questions_by_category[$category][$row]['question'] = str_replace(' & ', ' and ', $questions_by_category[$category][$row]['question']);
			$clues[$row + 1][] = $questions_by_category[$category][$row] + ['amount' => (($row + 1) * $multiplier)];
		}
	}
	return $app['twig']->render('jeopardy.html', [
		'categories' => $categories,
		'clues' => $clues,
	]);
});


