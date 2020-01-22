#Address Finder In laravel
How to Run This Project:
* Clone or Download The Source
* Extract 
* Build Database and name it : AddressFinder
* Run ``` php artisan migrate``` 
* Run ``` php artisan serve```
* Run ```php artisan queue:work --daemon --timeout=3000``` 
> i use --timeout=3000 for BigData . 
> for example in my case with default number(60) the app work on 20 - 30 Addressess . 
> in 3000 app can work on 1000 or more in one job . 
# OR
you can run this command in extracted folder 
``` bash Start.sh```
and give database name , username and password of mysql and its run your project
* Enjoy it

