# SqlQuery
SqlQuery - простой конструктор запросов.
Требуемая версия PHP: <b>5.3+</b>

#### Внимание! Описание не полное и будет постепенно дополняться.

# Установка
- Скачать последнюю версию библиотеки: <b>https://github.com/mvcbox/sql-query/archive/master.zip</b>
- Распаковываем содержимое архива в директорию с Вашим проектом.
- Открываем файл <b>connection.config.php</b> и указываем свои данные для подключения. Для MySQL необходимо указать: username, password, host, dbname. Остальное, в большинстве случаев, можно оставить без изменений. 
- В Вашем скрипте, где необходимо работать с базой данных, "подключаем" файл <b>function.qb.php</b>
```php
require_once 'Путь к файлу function.qb.php';
```

У вас теперь доступна функция qb(). Библиотека готова к использованию.

# Insert

##### Добавление одной записи в таблицу
```php
qb()->table('table_name')->insert(array(
  'column1' => 'value1',
  'column2' => 'value2',
  'column3' => 'value3'
));
```

##### Добавление нескольких записей в таблицу
```php
qb()->table('table_name')->insert(array(
  array(
    'column1' => 'value1',
    'column2' => 'value2',
    'column3' => 'value3'
  ),
  array(
    'column1' => 'value4',
    'column2' => 'value5',
    'column3' => 'value6'
  ),
  array(
    'column1' => 'value7',
    'column2' => 'value8',
    'column3' => 'value9'
  )
), true);
```

# Update

##### Для всех записей устанавливаем новое значение для поля column
```php
qb()->table('table_name')->update(array(
  'column' => 'New value'
));
```

##### Обновляем значение поля column для записи с id равным 123
```php
qb()->table('table_name')->where(array(
    'id' => 123
))->update(array(
    'column' => 'New value'
));
```

##### Обновляем значение поля column для записей с id 123, 456 и 789
```php
qb()->table('table_name')->where(array(
    'id' => array(123, 456, 789)
))->update(array(
    'column' => 'New value'
));
```

##### Обновляем значение поля column для всех записей, кроме записи с id 777
```php
qb()->table('table_name')->where(array(
    array('id', '<>', 777)
))->update(array(
    'column' => 'New value'
));
```

# Delete

##### Удаляем все записи в таблице
```php
qb()->table('table_name')->delete();
```

##### Удаляем запись с определенным id
```php
qb()->table('table_name')->where(array('id' => 123))->delete();
```

# Truncate

##### Очищаем таблицу
```php
qb()->table('table_name')->truncate();
```

# Select

##### Получить все записи таблицы 'table_name'
```php
$result = qb()->table('table_name')->all();
```

##### Получить первую запись таблицы table_name
```php
$result = qb()->table('table_name')->one();
```

##### Получить $limit записей пропуская первые $offset записей для таблицы table_name. limit и offset можно использовать отдельно друг от друга
```php
$result = qb()->table('table_name')->limit($limit)->offset($offset)->all();
```
##### Выборка с условием. Получить все записи таблицы table_name, у которых поле email равно email@site.com
```php
$result = qb()->table('table_name')->where(array(
  'email' => 'email@site.com'
))->all();
```

##### Получить все записи таблицы table_name, у которых поле email равно email1@site.com, или email2@site.com, или email3@site.com
```php
$result = qb()->table('table_name')->where(array(
  'email' => array(
    'email1@site.com',
    'email2@site.com',
    'email3@site.com'
  )
))->all();
```

##### Получить все записи таблицы table_name, у которых username равно testuser, email равно email@site.com, role равно 1, 2 или 3, а status НЕ равен 0
```php
$result = qb()->table('table_name')->where(array(
  'username'  => 'testuser',
  'email'     => 'email@site.com',
  'role'      => array(1, 2, 3),
  array('status', '<>', 0)
))->all();
```

##### Выбираем для записей только поля id и username
```php
$result = qb()->table('table_name')->select(array('id', 'username'))->all();
```

##### Выбираем для записей только поля id и username. Для username используем алиас login
```php
$result = qb()->table('table_name')->select(array('id', 'login' => 'username'))->all();
```

##### Получить все записи таблицы 'table_name'. Для таблицы 'table_name' задаем алиас 't1'
```php
$result = qb()->table(array('t1' => 'table_name'))->all();
```

##### INNER JOIN
```php
$result = qb()->table(array('t1' => 'table_name_1'))->iJoin(
  array('t2' => 'table_name_2'), 
  'field_name_2', 
  'field_name_1'
)->all();
```
или
```php
$result = qb()->table(array('t1' => 'table_name_1'))->iJoin(
  array('t2' => 'table_name_2'), 
  't2.field_name_2', 
  't1.field_name_1'
)->all();
```

##### INNER JOIN - несколько условий
```php
$result = qb()->table(array('t1' => 'table_name_1'))->iJoin(
  array('t2' => 'table_name_2'), 
  array(
    'field_name_2_1' => 'field_name_1_1',
    'field_name_2_2' => 'field_name_1_2'
  )
)->all();
```
или
```php
$result = qb()->table(array('t1' => 'table_name_1'))->iJoin(
  array('t2' => 'table_name_2'), 
  array(
    't2.field_name_2_1' => 't1.field_name_1_1',
    't2.field_name_2_2' => 't1.field_name_1_2'
  )
)->all();
```
или
```php
$result = qb()->table(array('t1' => 'table_name_1'))->iJoin(
  array('t2' => 'table_name_2'), 
  array(
    array('field_name_2_1', 'field_name_1_1'),
    array('field_name_2_2', 'field_name_1_2')
  )
)->all();
```
или
```php
$result = qb()->table(array('t1' => 'table_name_1'))->iJoin(
  array('t2' => 'table_name_2'), 
  array(
    array('t2.field_name_2_1', 't1.field_name_1_1'),
    array('t2.field_name_2_2', 't1.field_name_1_2')
  )
)->all();
```
