<?php

require_once __DIR__ . '/../vendor/autoload.php';

$parterNo  = '79112216';
$parterKey = '{"pubKey":"MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAprk1sFl15f++Cm4YodWz8PgjreusC3B5zwTCTZgrG9yi4xTe+Jpf3BRnwoDob2oWSo0M54iw9xqy5zbuiJYoQWJ/K1nneh2gwy13ndJIWpEIozzRf4eMGOUidID94pGgEZXxSUIh21n7S3Ev9OGUnqAkfZbSnLuxrf1KSwyzKJVSuFHO4SjldTOhES0u+le3+srEwf7Z5vlqQpN6jPV+h1PH7eO8F8VZmyoIguIOacjsspUryDgDq+ucGSPebD0g7NOoZDw75OO9JqCLZrJO2Fn3176CHafhwcAmn2L4HFlbTW64IIW4vv15qOqxwI5i1RaO4Dyq1HPI3tAjtFaheQIDAQAB","priKey":"MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCmuTWwWXXl/74Kbhih1bPw+COt66wLcHnPBMJNmCsb3KLjFN74ml/cFGfCgOhvahZKjQzniLD3GrLnNu6IlihBYn8rWed6HaDDLXed0khakQijPNF/h4wY5SJ0gP3ikaARlfFJQiHbWftLcS/04ZSeoCR9ltKcu7Gt/UpLDLMolVK4Uc7hKOV1M6ERLS76V7f6ysTB/tnm+WpCk3qM9X6HU8ft47wXxVmbKgiC4g5pyOyylSvIOAOr65wZI95sPSDs06hkPDvk470moItmsk7YWffXvoIdp+HBwCafYvgcWVtNbrgghbi+/Xmo6rHAjmLVFo7gPKrUc8je0CO0VqF5AgMBAAECggEACgp/hEZvBlIXtPMagqynMK0RIQlfjDTS8TfdJ+UxO3VXTgvUve42Nb/428r9tLAT9Zt3Gn6uAcPW4Hv7c7hvZjizZf39Jeqg4wBbAuNGPkYb2hDwoRidxGYQZCncoIeat1RrerkD5v0NWGbRt5Laa49Dg1jRCGV5dvwZPFngdGIokicOMVPMOFQmQA0foT0xdunumUasy67aUNX4pcy1rMf4R1Yl3lgfRfgHTGSHQtDDyDyFG56g1feEZPTEv7x7PHAZF1BXk+BM5Hj53J5EJTMjZe9gm3ml41J+14yx+l6JMJl9JFAYRmBAmRTeYJgZRWPTPDYHEmMbCoSzufAOzQKBgQD4M2bW2yDmmdxx0DK3MOoiY6e1cI+5FiHwh8kFCYMHozvep5hCTyjEDKweIEpYcRtI5LfOLLRZdmhv7Yxd2gheAO1uQ1HhyGtcedvxdELVbzBqSGIAZMI29JOComzvKxgxPohnZ91HJ41OC8IXQYsBougemIzY3I/aXNWOxFk2GwKBgQCr9mGTAz4utv7O/U5JdCZCHAIT+XeOJT8e8ltS1BvpRWfQad/9i8YvEqfcvMImvVSMlZytsyPnI2TLf8TLD9Lj8K1gPTsb5ZtqrY4Ibcopu7W9fIB38CQJ0ohn7OftHRDwABFVeArtUA8DP+znMd1Q4naFjTLykNE5cdhzK0kP+wKBgBX/8GChAbSO9MQ90RD9y/MKZEYn2QVt2cpJLt88bZnStS7SKiPOgm2GNgdhk1VtiR6X71beDYpUfYNWSffTqKQ0btc0LmDV8/Y5cu5fAMGMXn09NzqTs8dNPHn6za8RYc6TnWGVCj8RWWwOjMCk9Z5Kg0qa+PkL69BOJDvIWaP7AoGALYb6iFiYcvfB+H0559qXcnNF3ZHzpC+aSZAyu3gqt5THinctdUw3OSlARgG3iVgg9WN66sX94GkPe40lRfm3hN+ol00+xAEDJ7gxXWvr1Sa8VCGNRbPPZsZVlOY09SL35AKwlCdHBVNn1h1rlD4wcQS0CmG6tlu++O16N+ui2VcCgYAdMmngR40pzSy0uJ8h2YsUd442l/xzZkowF/032iocjukDPVMP3RmRLz6+RQZ9eFA5TR12qi7bJxuHsXmcwvHEbghBfs8Sf8ddBr+a1Bck3Ak/aUalogDnXXkz18b0/a2nea0YEkPJAzvQKTD+9ANvhMgRSG+jX36kPgoHoejA+g==","md5Key":"wek54*fg4h-354g+fgsdfg","merKey":"MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArorn7UwcyMmJ2SSm0Unu42xdAOawiV4wPf8GnnFIpm+pnaI5lSlmLFU4t5n8PipRFTR62IeJ4vBYIoWMR4zUexNxOVojD1SBT1VKXQBh7Lp7NFEkbny/yQ9jo0Ykc7HhARWLWpnmTlJFIjd8ZMbfQyYBB6oOraAl/kYAYiRsDYaGXSJMYjDemEDA+Jeb51B2kGwOWtSS8qtMzLteAHVkXYNQ3diX+n7QClWcLja5qx2q3BMjPqO/6gAojAwxQlic6gs326rjMN9Zlgg/Fiw7bO7e6YRHpYgiaXWHijNCOh+vw9RjcE7+9IE+V2ceHUq6Pn5iJhc8F/XljWkWfI7urQIDAQAB"}';

// $response = Weiming\Libs\AgencyPayments\Shangma::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey])->generateSignature([
//     'orderNo'  => date('YmdHis') . mt_rand(100, 999),
//     'bankCode' => 'CMB',
//     'acctId'   => '6226096554880818',
//     'acctName' => '冷春',
//     'mobile'   => '13577890000',
//     'bankName' => '招商银行',
//     'tranAmt'  => 100,
//     'province' => '省',
//     'city'     => '市',
//     // 'branch'   => '新洲支行',
// ], 'payment')->sendRequest();

// var_dump($response);

// echo "============================================================\n";

$response = Weiming\Libs\AgencyPayments\Shangma::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey])->generateSignature([
    'orderNo'  => '20180129093745833', // '20180129081553678', // '20180129082241496',
], 'query')->sendRequest();

var_dump($response);
