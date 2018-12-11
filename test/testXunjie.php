<?php

require_once __DIR__ . '/../vendor/autoload.php';

$key = '{"pubKey":"MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyzTUsJhcvhADZzOvZYFTD4rldS7ro4nKWvinzK2fj09iEn97uJyFvfVnSk/8uLyzwgCyo3VRLVA1hMrsjzIpZztW6MooAi8b/GGqo9/vsr4wAWMobyDokh1WKaOrOVyO1rVM/kQpUFp9nvapmBMC0T0aR+NTTLxnwhFVCbIFYlv0OvJo3bNTn0KeIRLq6FBXyIZouzI1/ziACxMQg1HhskAKfVVojHiyNetVSa7eZxlnB+YCvRuvW9JaIuaTt89IJTI25DIJTf3D+wIkYacuXAi4B/Ee3vSjaUR1e2ofbiUPyAPgdc1s/wO9OdTrKIQUVEsNJJG+9kZe5Ji16zP1lwIDAQAB","priKey":"MIIEogIBAAKCAQEAyzTUsJhcvhADZzOvZYFTD4rldS7ro4nKWvinzK2fj09iEn97uJyFvfVnSk/8uLyzwgCyo3VRLVA1hMrsjzIpZztW6MooAi8b/GGqo9/vsr4wAWMobyDokh1WKaOrOVyO1rVM/kQpUFp9nvapmBMC0T0aR+NTTLxnwhFVCbIFYlv0OvJo3bNTn0KeIRLq6FBXyIZouzI1/ziACxMQg1HhskAKfVVojHiyNetVSa7eZxlnB+YCvRuvW9JaIuaTt89IJTI25DIJTf3D+wIkYacuXAi4B/Ee3vSjaUR1e2ofbiUPyAPgdc1s/wO9OdTrKIQUVEsNJJG+9kZe5Ji16zP1lwIDAQABAoIBABspXKSeOQdOrKXGytMOjkOtlS4sr2KUsG+ofQVmz8UgH0PJtofepCHYA45zUpx+3Kg536bpr4gXCJBPb39UfSRHUj6bhu2DzoHVrDFrZWbG9TO6RVVVWMDvGu1F60UnkhAw+7Gg+sS607/DyLeDEwVU3YZuZFwFcvkFyBAbs4yJ8NpdLqiTC6UgPTmRgnkg/MrKDqOo97SBZImOWNW2481eplBKTXXEABiEFlAxpYYjMLgVr+P3KngkFjFb5l64q0vhRodPs9Txh8ryv+wNS7xBJ9yyneF0DweITqh0gMIt7tX8KxrZFj9/KRb2a0qICIpGnvj9W82ntxciy7yvfkECgYEA9p3e1FZGA8bp2oesT7HOIocxP1105Ut/100/RFyFGm7TXZP36zz3yTkY8P0VbhxnsNpSl8AfBnChJCibg7am9CQuc95lFSG0TONUb5CyUjVcnd+5fvzHGwv5H3LsoJUKBmHQEHJY/ZWPKNxPsDlNMQgrKQqE7TLKIoFgT5aeqL0CgYEA0vAhHDFXrg60zwCKBjYsd8ZkjMsqKbujWpHKYEF1w6HN0wziHTiMqX+fOduuFC9R5CORyDjdYLKoQjWdXU/5CymPf3Cxvgggad6POtAaRcdgd7xeH+GnUri5vODjKJ46kZWS7ucLMpwHncWSQa/CmsFHIZmSpBYk5L/61DoSDuMCgYAc+p4Vi3G8veH66NhpoPYc/o/d50h2LTH/hbc6fDizy3QQ2fNW9BoVzhzNLKAZCUNx96We6Vd79T4qNo9Xod3qcFn5CZgNLKG4Kzpfxbv+hwC0GHb+ogtvdS+371Q45mqAJ3xAgn9MhQeGgKToi0Mz0Mbpkq/eA4rXFSbRE1zfbQKBgA3NKF1v3QzGaY4huuYtJTuw/2M258tTO5zxbQxfPBVQwObOMP04dfuhHgnrFbi8X99NdHxlPBbXmaq7V1CDqwfP8FOmS/rjDTvgE/8FHrLyGJ289WugmBIRhBWXoUVHFQ3xe2umMlVdYCIyix9BILp/augH5FBYIpHU/dRk+EmBAoGAAqrMPEojzgq1SQc+TTdt2pwTnz+kKZ49yDA31tTisQ5y5U9OxI19Zhv6XtlMfmzUxFKbdbkS8tO5LwAh2iVt8xwjxEKCmz2eWLePoW2QB2ruFl550pX8LjK/yZJCiIHpDETM9ZKWLek89KlByTs8UQRDf1pnlpH+SEtYAAW41NI="}';

echo \Weiming\Libs\Payments\Xunjie::getInstance([
    'parterNo' => '850440050945378',
    'parterKey' => $key,
    'callbackUrl' => 'http://47.90.98.83:9898/payment/xunjie/callback',
    'notifyUrl' => 'http://47.90.98.83:9898/payment/xunjie/notify'
])->signature('0122', 100, date('YmdHis'))->payment();

// $orderNo = date('YmdHis');

// echo $orderNo . "\n";

// var_dump(\Weiming\Libs\AgencyPayments\Xunjie::getInstance(['parterNo' => '850510059375264', 'parterKey' => $key])->generateSignature(['orderNo'  => $orderNo, 'acctId'   => '6226096524880818', 'acctName' => '张三', 'tranAmt'  => 1], 'payment')->sendRequest());
// var_dump(\Weiming\Libs\AgencyPayments\Xunjie::getInstance(['parterNo' => '850510059375264', 'parterKey' => $key])->generateSignature(['orderNo'  => $orderNo], 'query')->sendRequest());
// var_dump(\Weiming\Libs\AgencyPayments\Xunjie::getInstance(['parterNo' => '850510059375264', 'parterKey' => $key])->generateSignature([], 'balanceQuery')->sendRequest());
