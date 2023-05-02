<?php


if (!function_exists('ok')) {
    function ok($message = null, $data = [], $status = 200,)
    {
        $response = [
            'status'    =>  $status,
            'message'   =>  $message ?? 'Process is successfully completed',
            'data'      =>  $data
        ];

        return response()->json($response, $status);
    }
}

if (!function_exists('error')) {
    function error($message = null, $data = [], $type = null)
    {
        $status = 500;

        switch ($type) {
            case 'validation':
                $status  = 422;
                $message ?? 'Validation Failed please check the request attributes and try again';
                break;

            case 'unauthenticated':
                $status  = 401;
                $message ?? 'User token has been expired';
                break;

            case 'notfound':
                $status  = 404;
                $message ?? 'Sorry no results query for your request';
                break;

            case 'forbidden':
                $status  = 403;
                $message ??  'You don\'t have permission to access this content';
                break;

            default:
                $status = 500;
                $message ?? $message = 'Server error, please try again later';
                break;
        }

        $response = [
            'status'    =>  $status,
            'message'   =>  $message,
            'data'      =>  $data
        ];

        return response()->json($response, $status);
    }
}
