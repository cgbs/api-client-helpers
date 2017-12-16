<?php 
use Wizz\ApiClientHelpers\Middleware\BlockUrlsMiddleware;
use Wizz\ApiClientHelpers\Middleware\UpdateGlobalsMiddleware;
Route::get('/sitemap.xml', '\Wizz\ApiClientHelpers\ACHController@proxy')->where('slug', '.+');

Route::get('/robots_generator', '\Wizz\ApiClientHelpers\ACHController@proxy')->where('slug', '.+');

Route::get('/r/{slug?}', '\Wizz\ApiClientHelpers\ACHController@redirect')->where('slug', '.+');

Route::any('api/{slug?}', '\Wizz\ApiClientHelpers\ACHController@proxy')->where('slug', '.*');

Route::get('/t/check', '\Wizz\ApiClientHelpers\ACHController@check');

Route::get('/t/clear_cache', '\Wizz\ApiClientHelpers\ACHController@clear_cache');
// TODO do we need this here?
Route::get('clients/payments/success', function(){
	$params = http_build_query(request()->all());
	return redirect()->to('https://api.speedy.company/clients/payments/success?'.$params);
})->where('slug', '.+');

Route::get('clients/payments/failure', function(){
	$params = http_build_query(request()->all());
	return redirect()->to('https://api.speedy.company/clients/payments/failure?'.$params);
})->where('slug', '.+');

Route::get('payments/failure', function(){
	$params = http_build_query(request()->all());
	return redirect()->to('https://api.speedy.company/clients/payments/failure?'.$params);
})->where('slug', '.+');

Route::get('payments/cancel', function(){
	$params = http_build_query(request()->all());
	return redirect()->to('https://api.speedy.company/clients/payments/cancel?'.$params);
})->where('slug', '.+');

Route::get('payments/pending', function(){
	$params = http_build_query(request()->all());
	return redirect()->to('https://api.speedy.company/clients/payments/pending?'.$params);
})->where('slug', '.+');
// TODO change to use in multisite mode.
if(env('use_frontend_repo') === true)
{
	
	/*

	This check is here to ensure that we are not fucking up projects,

	where frontend_repo is not explicitly enabled.

	*/
	// dd('qwe');
	Route::get('{slug?}', '\Wizz\ApiClientHelpers\ACHController@frontend_repo')
		->middleware(UpdateGlobalsMiddleware::class)	
		->middleware(BlockUrlsMiddleware::class)
		->where('slug', '.+');	

}

