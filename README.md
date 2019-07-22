# ZchanHttp

## Usage of Cookie.php

```
$cookie = new \zero0x00chan\Cookie();
$cookie->SetName('cname1');
$cookie->SetValue('cvalue1');
$cookie->SetExpiry(time() + (86400 * 30));
$cookie->SetMaxAge(35355);
$cookie->SetPath('/some/path'); # default is /
$cookie->SetDomain('www.example.com');
$cookie->HttpOnly(bool);
$cookie->Secure(bool);

if ( !$cookie->Add() ) {
    echo $cookie::$error;
}

$cookie->SetName('cname2');
$cookie->SetValue('cvalue2');
$cookie->SetExpiry(time() + (86400 * 30));
$cookie->SetMaxAge(35355);
$cookie->SetPath('/some/path'); # default is /
$cookie->SetDomain('www.example.com');
$cookie->HttpOnly(bool);
$cookie->Secure(bool);

if ( !$cookie->Add() ) {
    echo $cookie::$error;
}

$cookie->Dispatch( 'cname1' );
$cookie->Dispatch( 'cname2' );
```