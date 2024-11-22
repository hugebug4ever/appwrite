<?php

namespace Appwrite\Auth\OAuth2;
use Utopia\CLI\Console;
use Appwrite\Auth\OAuth2;

// Reference Material
// https://developers.weixin.qq.com/doc/oplatform/Mobile_App/WeChat_Login/Authorized_API_call_UnionID.html
// 返回值说明:
//    https://developers.weixin.qq.com/doc/oplatform/Return_codes/Return_code_descriptions_new.html
//    42001 access_token 超时
//    42002 refresh_token 超时
//    42003 oauth_code 超时
class Wechat extends OAuth2
{
    private string $endpoint = 'https://api.weixin.qq.com/sns';
    protected array $user = [];
    protected array $tokens = [];
    protected array $scopes = [
        // [ARRAY_OF_REQUIRED_SCOPES]

        'snsapi_userinfo',
    ];
    protected string $openid = '';

    public function getName(): string
    {
        return 'wechat';
    }

    public function getLoginURL(): string
    {
        // xilei: for native api, I dont need it right now
        // $url = $this->endpoint . '[LOGIN_URL_STUFF]';

        $url = "https://wx.qq.com/";
        return $url;
    }

    protected function getTokens(string $code): array
    {
        if (empty($this->tokens)) {
            // TODO: Fire request to oauth API to generate access_token
            // Make sure to use '$this->getScopes()' to include all scopes properly
            // $this->tokens = ["[FETCH TOKEN RESPONSE]"];

            $query = $this->endpoint . '/oauth2/access_token?' .
                    \http_build_query([
                    'appid' => $this->appID,
                    'secret' => $this->appSecret,
                    'code' => $code,
                    'grant_type' => 'authorization_code']);
            $resp = $this->request('GET', $query);
            // check error
            $error = \json_decode($resp, true);
            if($error['errcode'] ?? 0) {
                Console::info("1.====================\n" . $resp);
                $oauth2Err=['error' => $error['errcode'], 'error_description' => $error['errmsg']];
                $oauth2ErrStr = \json_encode($oauth2Err);
                throw new Exception($oauth2ErrStr);
            }
            $this->tokens=\json_decode($resp, true);
            $this->openid = $this->tokens['openid'] ?? '';

            // tokens 可能已经过期
            // code 虽然更新，但 access_token 未必更新, 需要 refresh_token 刷新状态

            // TODO: revise following code
            // $query = $this->endpoint . "/sns/auth" .
            //         \http_build_query([
            //             'access_token' => $this->tokens['access_token'],
            //             'openid' => $this->openid,
            //         ]);
            // $resp = $this->request('GET', $query);
            // $error = \json_decode($resp, true);
            // if($error['errcode'] ?? 0) {
            //     Console::info("2.====================\n" . "query: " .$query ."\n" . "resp: ". $resp);
            //     $oauth2Err=['error' => $error['errcode'], 'error_description' => $error['errmsg']];
            //     $oauth2ErrStr = \json_encode($oauth2Err);
            //     throw new Exception($oauth2ErrStr);
            // }
        }

        return $this->tokens;
    }

    public function refreshTokens(string $refreshToken): array
    {
        // TODO: Fire request to oauth API to generate access_token using refresh token
        // $this->tokens = ["[FETCH TOKEN RESPONSE]"];

        $query = $this->endpoint . '/oauth2/refresh_token?' .
                \http_build_query([
                'appid' => $this->appID,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token']);
        $resp = $this->request('GET', $query);
        $this->tokens=\json_decode($resp, true);
        $this->openid = $this->tokens['openid'] ?? '';

        return $this->tokens;
    }

    public function getUserID(string $accessToken): string
    {
        $user = $this->getUser($accessToken);

        // TODO: Pick user ID from $user response
        // $userId = "[USER ID]";

        $userId = $this->openid;

        return $userId;
    }

    public function getUserEmail(string $accessToken): string
    {
        $user = $this->getUser($accessToken);

        // TODO: Pick user email from $user response
        // $userEmail = "[USER EMAIL]";

        // account service requires default email for user creation as last resort
        // but wechat does not provide email, so we use openid as email
        // it's never would be able to be verified.
        // but it's would be new account's default email
        $userEmail = $this->openid . "@xx.com";

        return $userEmail;
    }

    public function isEmailVerified(string $accessToken): bool
    {
        $user = $this->getUser($accessToken);

        // TODO: Pick user verification status from $user response
        // $isVerified = "[USER VERIFICATION STATUS]";
        $isVerified = false;

        return $isVerified;
    }

    public function getUserName(string $accessToken): string
    {
        $user = $this->getUser($accessToken);

        // TODO: Pick username from $user response
        // $username = "[USERNAME]";

        $username = $user['nickname'] ?? '';

        return $username;
    }

    protected function getUser(string $accessToken): array
    {
        if (empty($this->user)) {
            // TODO: Fire request to oauth API to get information about users
            // $this->user = "[FETCH USER RESPONSE]";

            $query = $this->endpoint . '/userinfo?' .
                    \http_build_query([
                    'openid' => $this->openid,
                    'access_token' => $accessToken]);
            $resp = $this->request('GET', $query);
            $this->user = \json_decode($resp, true);
        }

        return $this->user;
    }
}
