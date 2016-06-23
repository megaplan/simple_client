# Тонкий API-клиент для Мегаплана

## Установка 

``composer require megaplan/simple_api_client``

## Использование 

```php

	$response = (new Client('myhost'))
		->auth('pupkin@megaplan.ru', 'idclip')
        ->get('/BumsTaskApiV01/Task/list.api');
        
    /*
        object(stdClass)#20 (2) {
          ["status"]=>
          object(stdClass)#18 (2) {
            ["code"]=>
            string(2) "ok"
            ["message"]=>
            NULL
          }
          ["data"]=>
          object(stdClass)#23 (1) {
            ["tasks"]=>
            array(1) {
              [0]=>
              object(stdClass)#17 (16) {
                ["Id"]=>
                int(1000000)
                ["Name"]=>
                string(47) "Начать работу в Мегаплане"
                ["Status"]=>
                string(8) "assigned"
                ["Deadline"]=>
                string(10) "2016-06-13"
                ["Owner"]=>
                object(stdClass)#21 (3) {
                  ["Id"]=>
                  string(7) "1000001"
                  ["Name"]=>
                  string(9) "User Name"
                  ["Avatar"]=>
                  string(35) "/60x60/s/7/i/sample/photo-small.jpg"
                }
                ["Responsible"]=>
                object(stdClass)#22 (3) {
                  ["Id"]=>
                  string(7) "1000001"
                  ["Name"]=>
                  string(9) "User Name"
                  ["Avatar"]=>
                  string(35) "/60x60/s/7/i/sample/photo-small.jpg"
                }
                ["Severity"]=>
                string(0) ""
                ["Favorite"]=>
                int(0)
                ["TimeCreated"]=>
                string(19) "2016-06-06 20:37:48"
                ["TimeUpdated"]=>
                string(19) "2016-06-06 20:37:48"
                ["Start"]=>
                string(19) "2016-06-06 20:37:48"
                ["Completed"]=>
                string(1) "0"
                ["Folders"]=>
                array(4) {
                  [0]=>
                  string(3) "all"
                  [1]=>
                  string(8) "incoming"
                  [2]=>
                  string(11) "responsible"
                  [3]=>
                  string(5) "owner"
                }
                ["IsOverdue"]=>
                bool(true)
                ["CommentsUnread"]=>
                int(0)
                ["Activity"]=>
                string(19) "2016-06-06 20:37:48"
              }
            }
          }
        }        
    */                 
```        
 
## Тестирование
 
``phpunit --bootstrap ./vendor/autoload.php tests`` 
