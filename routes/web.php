<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    // return view('checkout');
    return [
        'status' => 'normal',
        'message' => 'Server work normally!',
    ];
});

Route::group(['namespace' => 'Admin', 'prefix' => 'admin'], function () {
    Route::group(['namespace' => 'Auth', 'prefix' => 'session'], function () {
        Route::any('/', 'SessionController@index');
        Route::post('login', 'SessionController@login');
        Route::post('logout', 'SessionController@logout');
    });

    Route::group(['prefix' => 'stores'], function () {
        Route::get('/', 'StoreController@index');
        Route::post('/', 'StoreController@create');
        Route::group(['prefix' => '{store}'], function () {
            Route::get('/', 'StoreController@show');
            Route::post('/', 'StoreController@update');
            Route::get('devices', 'StoreController@devices');
            Route::post('delete', 'StoreController@delete');
            Route::post('add-trunks', 'StoreController@addTrunks');
            Route::group(['prefix' => 'trunks'], function () {
                Route::get('/', 'TrunkController@index');
                // Route::get('devices', 'StoreController@devices');
            });
        });
    });

    Route::group(['prefix' => 'partners'], function () {
        Route::get('/', 'PartnerController@index');
        Route::post('/', 'PartnerController@create');
        Route::group(['prefix' => '{partner}'], function () {
            Route::get('/', 'PartnerController@show');
        });
    });

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', 'UserController@index');
    });

    Route::group(['prefix' => 'vegetables'], function () {
        Route::get('/', 'VegetableController@index');
        Route::post('/', 'VegetableController@create');
        Route::group(['prefix' => '{vegetable}'], function () {
            Route::get('/', 'VegetableController@show');
        });
    });

});

Route::group(['namespace' => 'User'], function () {
    Route::group(['namespace' => 'Auth', 'prefix' => 'session'], function () {
        Route::any('/', 'SessionController@index');
        Route::post('login', 'SessionController@login');
        Route::post('logout', 'SessionController@logout');
        Route::post('register', 'SessionController@register');
    });

    Route::group(['prefix' => 'stores'], function () {
        Route::get('/', 'StoreController@index');
        Route::get('{store}', 'StoreController@show');
    });

    Route::group(['prefix' => 'orders'], function () {
        Route::get('/', 'OrderController@index');
        Route::get('{order}', 'OrderController@show');
    });

    Route::group(['prefix' => 'cart'], function () {
        Route::get('/', 'CartController@index');
        Route::group(['prefix' => 'checkout'], function () {
            Route::get('/', 'CartController@checkout');
            Route::get('return/{order}', 'CartController@checkoutReturn');
            Route::get('cancel/{order}', 'CartController@checkoutCancel');
        });
        Route::group(['prefix' => 'items'], function () {
            Route::get('/', 'CartController@index');
            Route::post('/', 'CartController@addItem');
            Route::post('delete', 'CartController@deleteItems');
            Route::post('{item}', 'CartController@updateItem');
        });
    });
});

Route::group(['namespace' => 'Partner', 'prefix' => 'partner'], function () {
    Route::group(['namespace' => 'Auth', 'prefix' => 'session'], function () {
        Route::any('/', 'SessionController@index');
        Route::post('login', 'SessionController@login');
        Route::post('logout', 'SessionController@logout');
    });

    Route::group(['prefix' => 'stores'], function () {
        Route::get('/', 'StoreController@index');
        Route::group(['prefix' => '{store}'], function () {
            Route::get('/', 'StoreController@show');
            Route::post('/', 'StoreController@update');
            Route::get('devices', 'StoreController@devices');
            Route::group(['prefix' => 'trunks'], function () {
                Route::get('/', 'TrunkController@index');
                // Route::post('/', 'StoreController@update');
                // Route::get('devices', 'StoreController@devices');
            });
        });
    });
});

Route::group(['namespace' => 'Device', 'prefix' => 'device'], function () {
    Route::group(['namespace' => 'Auth', 'prefix' => 'session'], function () {
        Route::any('/', 'SessionController@index');
        Route::post('login', 'SessionController@login');
        Route::post('logout', 'SessionController@logout');
    });
});
