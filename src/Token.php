<?php
namespace Wizz\ApiClientHelpers;

use \Illuminate\Http\Request;
use \Cache;

class Token
{
    // TODO rewrite to use with multisite client
    // This is a singleton object which contains initial data and token
    protected $data = '';

    public $errors;

    public $cookies;

    public $request;

    protected function getFromBootstrap($query)
    {
        

        $cache_key = 'bootstrap_data_from_api';
        // if (false)
        if (Cache::has($cache_key)) {
            $output = Cache::get($cache_key);
        } else {
            // $addition = array_get($_SERVER, 'QUERY_STRING', '');
            // $query .= ($addition) ? '&'.$addition : '';
            session(['addition' => request()->all()]);

            // $cookie_string = getCookieStringFromRequest(request());
            
            // session_write_close();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $query);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            // curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);
            $res = curl_exec($ch);
            curl_close($ch);

            $data = explode("\r\n\r\n", $res);
            $headers = (count($data) == 3) ? $data[1] : $data[0];
            $res = (count($data) == 3) ? $data[2] : $data[1];
            setCookiesFromCurlResponse($headers);

            $output = json_decode($res);

            Cache::put($cache_key, $output, 100);
        }
        if (!is_object($output)) {
            $this->errors = $res;
            return 'false';
        }
        if (property_exists($output, 'errors') && count($output->errors) > 0) {
            $this->errors = $output->errors;
            return false;
        }

        $this->data = $output->data;

        return true;
    }
    
    public function getBootstrapData()
    {
        if (is_object($this->data)) {
            return $this->data->bootstrap;
        }
        
        return [];
    }

    public function getToken()
    {
        if (is_object($this->data)) {
            $access_token = $this->data->access_token;

            session(['access_token' => $access_token]);
            
            return $access_token;
        }

        return '';
    }

    public function init()
    {
        return $this->getFromBootstrap($this->prepareQuery());
    }

    private function prepareQuery()
    {
        $form_params = [
            'grant_type' => config('api_configs.grant_type'),
            'client_id' => config('api_configs.client_id'),
            'client_secret' => config('api_configs.client_secret'),
        ];
        return conf('secret_url').'/oauth/access_token'.'?'.http_build_query($form_params);
    }
}
