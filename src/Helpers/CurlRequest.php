<?php
namespace Wizz\ApiClientHelpers\Helpers;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use \Illuminate\Http\Request;
use Wizz\ApiClientHelpers\Helpers\ArrayHelper;
use Wizz\ApiClientHelpers\Helpers\CookieHelper;

class CurlRequest 
{
    protected $request;

    public function __construct(Request $request){
        $this->request = $request;
    }
    private $post_methods = ["PUT", "POST", "DELETE"];

    public $redirect_statuses = [301, 302];

    public $headers = ['cookies' => []];
    public $cookies = [];
    // response body
    public $body;

    public $curl_info;

    public $content_type;
    public $response_status;
    public $redirect_status = false;
    public $raw_response;


    public function execute()
    {
        // maybe we should use https://github.com/php-curl-class/php-curl-class/blob/master/src/Curl/Curl.php
        $path = $this->request->path();
        // TODO do we need it here?
        $path = strpos($path, '/') === 0 ? $path : '/'.$path;
        $requestString = str_replace(conf('url'), '', $path);
        $method = $_SERVER['REQUEST_METHOD'];
        $data = $this->request->all();
        $data['ip'] = array_get($_SERVER, 'HTTP_CF_CONNECTING_IP', $this->request->ip());
        $data['app_id'] = conf('client_id');
        $addition = session('addition') ? session('addition') : [];
        $data = array_merge($data, $addition);

        $query = conf('secret_url').$requestString;
        $query .= ($method == "GET") ? '?'.http_build_query($data) : '';
        $cookie_string = CookieHelper::getCookieStringFromArray($this->request->cookie());
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept-Language: '.$this->request->header('Accept-Language')]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, [$this, 'setHeaders']);
        
        if (in_array($method, $this->post_methods)){
            if (array_get($data, 'files')) $data['files'] = $this->prepare_files_for_curl($data);
            $data = ($method == "POST") ? ArrayHelper::array_sign($data) : http_build_query($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $this->raw_response = curl_exec($ch);
        $this->curl_info = curl_getinfo($ch);
        curl_close($ch);
        $this->setBody()->setInfo();    
    }


    private function setHeaders($curl, string $header_line){
        if(! strpos($header_line, ':')) return strlen($header_line);
        
        list($name, $value) = explode(':', trim(strtolower($header_line)), 2);
        if  ($name == 'set-cookie') {
            $this->headers['cookies'][] = CookieHelper::parse_cookies(trim($value));
        } else {
            $this->headers[$name] = trim($value);
        }
        return strlen($header_line);
    }

    private function setInfo(){
        $this->response_status = $this->curl_info['http_code'];
        $this->content_type = $this->curl_info['content_type'];
        $this->redirect_status = in_array($this->response_status, $this->redirect_statuses) ? $this->response_status : false;
        return $this;
    }


    private function setBody(){
        $body = explode("\r\n\r\n", $this->raw_response);
        $this->body = (count($body) == 3) ? $body[2] : $body[1]; 
        return $this;
    }
    
    public function prepare_files_for_curl(array $data, $file_field = 'files')
    {
        $files = ArrayHelper::array_sign(array_pull($data, $file_field));
        foreach ($files as $key => $file){
            if (is_object($file) && $file instanceof UploadedFile){
                $files[$key] = new CURLFile($file->getRealPath(), $file->getClientOriginalName(), $file->getMimeType());
            }
        }
        return $files;
    }

}



