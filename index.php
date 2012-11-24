<?php

require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();
$app['debug'] = true;
$app['zilex.index'] = 'install';

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
	'twig.options' => array('cache' => __DIR__.'/../cache'),
));

$app->get('/summary', function () use ($app) {
	if(false == $app['debug']) {
		$app->abort(404, "Page does not exist");
	}
	$dir_handle = @opendir($app['twig.path'].'/content') or die("Unable to open $path");
	$templates = array();
	while ($file = readdir($dir_handle)) {
		if(!in_array($file, array('.', '..', '.svn'))){
			$templates[] = substr($file, 0, -5);
		}
	}
	closedir($dir_handle);

	return $app['twig']->render('tools/summary.twig', array(
		'templates' => $templates
	));
});

$app->get('/{page}', function ($page) use ($app) {
	try{
		return $app['twig']->render('content/'.$page.'.twig', array(
			'pageName' => $page,
		));
	} catch (Exception $e){
		if('Twig_Error_Loader' == get_class($e)){
			$app->abort(404, 'Twig template does not exist.');
		}else {
			throw $e;
		}
	}
})
->value('page', $app['zilex.index']);;


$app->error(function (\Exception $e, $code) use ($app) {
	if($app['debug']) {
		return;
	}
	switch ($code) {
		case 404:
			return new Response( $app['twig']->render('content/404.twig'), 404);
			break;
		default:
			$message = 'We are sorry, but something went terribly wrong.';
	}

	return new Response($message);
});

$app->run();