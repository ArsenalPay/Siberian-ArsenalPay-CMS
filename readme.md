# ArsenalPay Module for Siberian 4.x CMS
*Arsenal Media LLC*  

## Version
*4.1.2*  
*Has been tested on Siberian 4.x*  

##### Basic feature list:  
 * Allows seamlessly integrate unified payment frame into your site.
 * New payment method ArsenalPay will appear to pay for your products and services.
 * Allows to pay using mobile commerce and bank acquiring. More methods are about to become available. Please check for updates.
 * Supports two languages (Russian, English).  
 
### Prepare to install
To install the module, you need to collect all the files in one zip archive:

### Install

To install the ArsenalPay payment module, you must do the following:
- Go to the page backoffice: http://address_yours_site/backoffice
- Go to Settings -> Modules
- Download the file with the module's archive using the form and after that on the same page in the "Module to upload" Click on the download button
- On the same page below, click install

### Customization

* Go to the features editing page in the application admin panel (by default http://address_yours_site/application/customization_features/list)
* In the installed applications, select the "commerce" feature.
* If you do not have a store, create it (My stores -> +) or if you already have a store, Go to store management (Manage -> Your_Shop)
* In the section of payment methods (Payment) choose Arsenalpay and configure:

* Widget ID - Unique identifier of the widget, mandatory;
* Callback key - The key for checking the signature of requests, mandatory;
* Widget Key - The key for checking the widget, mandatory;
* Callback URL - payment check URL

### Usage

Before use, make sure that in the admin area in the feature commerce
Added a product or service that can be purchased (see https://doc.siberiancms.com/knowledge-base/mcommerce/)

* For ease of checking the widget, you need to go into the view mode (http://address_yours_site/overview)
* Before buying, in the "Commerce" feature you need to register and enter the "My account" feature.
* Add the product / service and go to the cart and go to payment.
* Select a product, click on the trolley icon in the upper right corner
* In the upper right corner click procced, fill in the data
* In the upper right corner, click next, select the delivery method, next
* Choose a payment method Arsenalpay => next => validate
* Maintain payment information and click to pay
* After completing the payment, click the Home button in the upper right corner

### DESCRIPTION OF THE SOLUTION
ArsenalPay is a convenient and reliable payment service for businesses of any size.

Using the payment module from ArsenalPay, you will be able to accept online payments from customers around the world with the help of:
Plastic cards of international payment systems Visa and MasterCard, issued in any bank
Mobile phone balance of MTS, Megafon, Beeline, Rostelecom and TELE2 operators
Various electronic wallets.

### Advantages of the service:
- [Lowest rates] (http://arsenalpay.com/tariffs.html)
- Free connection and maintenance
- Easy integration
- [Agency scheme: monthly payments to developers] (http://arsenalpay.com/partnership.html)
- Withdrawal of funds to a current account without commission
- SMS sms service
- Personal account
- 24-hour customer service support

And we can take on your technical support for your site and create mobile applications for you and Android.

ArsenalPay - to increase profit simply!
We work 7 days a week and 24 hours a day. And together with us a lot of Russian and foreign companies.

### How to connect:
1. You downloaded the module and installed it on your site;
2. Send us a link to your website at pay@arsenalpay.ru or leave a request on the [site] (http://arsenalpay.com/#register) via the "Connect" button;
3. We will send you commercial terms and technical settings;
4. After your consent we will send you a draft contract for consideration.
5. Sign the contract and get down to work.

We are always happy to receive your letters with offers.

Pay@arsenalpay.ru

[Arsenalpay.com] (http://arsenalpay.com)



###########################################



### О модуле
Модуль платежной системы "ArsenalPay" под Siberian позволяет легко встроить платежную страницу на Ваш сайт.
После установки модуля у Вас появится новый вариант оплаты товаров и услуг через платежную систему "ArsenalPay".
Платежная система "ArsenalPay" позволяет совершать оплату с различных источников списания средств: мобильных номеров
(МТС/Мегафон/Билайн/TELE2), пластиковых карт (VISA/MasterCard/Maestro). Перечень доступных источников средств постоянно
пополняется. Следите за обновлениями.

---
### Подготовка к установке
Для установки модуля, необходимо все файлы собрать в один zip архив :
* В Windows используйте архиватор (7-zip, winrar и т.п.)
* В linux в консоле перейти в раздел с модулем и выполнить команду:

```sh
zip -r Arsenalpay .
```
---

### Установка

Для установки платежного модуля ArsenalPay необходимо произвести следующие действия:
- Перейти на страницу backoffice : http://адрес\_вашего\_сайта/backoffice
- Перейти в раздел Settings -> Modules
- Загрузить файл с архивом модуля используя форму и после этого на этой же странице в "Module to upload" нажать на кнопку загрузки
- На этой же странице ниже нажать кнопку install

---

### Настройка

* Убедиться, что для приложения выбрана страна Россия : Перейти на страницу редактирования design в админке приложени (по умолчанию http://адрес_вашего_сайта/application/customization_design_style/edit) и в разделе "Choose your country" выбрать страну Russia (руб.) 
* Перейти на страницу редактирования features в админке приложения (по умолчанию http://адрес_вашего_сайта/application/customization_features/list )
* В установленных приложениях выбрать фичу "commerce".
* Если у вас не создан store, создать его (My stores -> + ) или если у вас уже есть store, перейти в управление магазином (Manage -> Ваш\_магазин) 
* В разделе методов оплаты (Payment) выбрать Arsenalpay и настроить:

* Widget ID - Уникальный идентификатор виджета , обязательный;
* Callback key - Ключ для проверки подписи запросов, обязательный;
* Widget Key - Ключ для проверки виджета, обязательный;
* Callback URL - УРЛ колбэка платежа

---
### Использование

Перед использованием, убедитесь что в в админке в фиче commerce
добавлен товар или услуга, которую можно приобрести (см. https://doc.siberiancms.com/knowledge-base/mcommerce/)

- Для удобства проверки виджета, нужно перейти в режим просмотра (http://адрес_вашего_сайта/overview)
- Перед покупкой, в фиче "Commerce" нужно зарегестрироваться и войти в фиче "My account".
- Добавить товар/услугу и перейти в корзину и перейти к оплате.
- Выбрать товар, нажать на иконку тележки в правом верхнем углу
- В правом верхнем углу нажать procced, заполнить данные
- В правом верхнем углу нажать next, выбрать способ доставки, next
- Выбрать способ оплаты Arsenalpay => next => validate
- Вести платежные данные  и нажать оплатить
- После завершения оплаты нажать кнопку Home в правом верхнем углу

------------------
### ОПИСАНИЕ РЕШЕНИЯ
ArsenalPay – удобный и надежный платежный сервис для бизнеса любого размера. 

Используя платежный модуль от ArsenalPay, вы сможете принимать онлайн-платежи от клиентов по всему миру с помощью: 
- пластиковых карт международных платёжных систем Visa и MasterCard, эмитированных в любом банке
- баланса мобильного телефона операторов МТС, Мегафон, Билайн, Ростелеком и ТЕЛЕ2
- различных электронных кошельков

### Преимущества сервиса: 
 - [Самые низкие тарифы](https://arsenalpay.ru/tariffs.html)
 - Бесплатное подключение и обслуживание
 - Легкая интеграция
 - [Агентская схема: ежемесячные выплаты разработчикам](https://arsenalpay.ru/partnership.html)
 - Вывод средств на расчетный счет без комиссии
 - Сервис смс оповещений
 - Персональный личный кабинет
 - Круглосуточная сервисная поддержка клиентов 

А ещё мы можем взять на техническую поддержку ваш сайт и создать для вас мобильные приложения для Android и iOS. 

ArsenalPay – увеличить прибыль просто! 

Мы работаем 7 дней в неделю и 24 часа в сутки. А вместе с нами множество российских и зарубежных компаний. 

### Как подключиться: 
- Вы скачали модуль и установили его у себя на сайте;
- Отправьте нам письмом ссылку на Ваш сайт на pay@arsenalpay.ru либо оставьте заявку на [сайте](https://arsenalpay.ru/#register) через кнопку "Подключиться";
- Мы Вам вышлем коммерческие условия и технические настройки;
- После Вашего согласия мы отправим Вам проект договора на рассмотрение.
- Подписываем договор и приступаем к работе.

Всегда с радостью ждем ваших писем с предложениями. 

pay@arsenalpay.ru 

[arsenalpay.ru](https://arsenalpay.ru)