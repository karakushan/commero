<?php

return array (
  'navigation' => 
  array (
    'catalog' => 'Каталог',
    'marketing' => 'Маркетинг',
    'orders' => 'Заказы',
    'content' => 'Контент',
    'access' => 'Доступ',
    'payment_and_shipping' => 'Оплата и доставка',
    'system' => 'Система',
  ),
  'common' => 
  array (
    'id' => 'ID',
    'code' => 'Код',
    'name' => 'Название',
    'title' => 'Заголовок',
    'slug' => 'Slug',
    'sort' => 'Сортировка',
    'price' => 'Цена',
    'availability' => 'Наличие',
    'status' => 'Статус',
    'type' => 'Тип',
    'description' => 'Описание',
    'excerpt' => 'Анонс',
    'content' => 'Контент',
    'full_description' => 'Полное описание',
    'locale' => 'Язык',
    'translations' => 'Переводы',
    'updated_at' => 'Обновлено',
    'created_at' => 'Создано',
    'published_at' => 'Дата публикации',
    'brand' => 'Бренд',
    'group' => 'Группа',
    'options' => 'Опции',
    'label' => 'Подпись',
    'identifier' => 'Идентификатор',
    'value' => 'Значение',
    'path' => 'Путь',
    'depth' => 'Уровень',
    'parent_category' => 'Родительская категория',
    'categories' => 'Категории',
    'sku' => 'Артикул',
    'thumbnail' => 'Миниатюра',
    'icon' => 'Иконка',
    'is_active' => 'Активен',
    'url' => 'URL',
    'phone' => 'Телефон',
    'email' => 'Email',
    'message' => 'Сообщение',
    'save' => 'Сохранить',
  ),
  'resources' => 
  array (
    'attribute_group' => 
    array (
      'singular' => 'группа атрибутов',
      'plural' => 'группы атрибутов',
      'navigation' => 'Группы атрибутов',
    ),
    'brand' => 
    array (
      'singular' => 'бренд',
      'plural' => 'бренды',
      'navigation' => 'Бренды',
    ),
    'category' => 
    array (
      'singular' => 'категория',
      'plural' => 'категории',
      'navigation' => 'Категории',
      'active_locale' => 'Язык перевода',
      'active_locale_hint' => 'Выберите язык, поля которого нужно редактировать.',
      'translation_fields' => 'Поля перевода',
      'editing_translation' => 'Редактирование перевода: :locale',
      'default_locale_required' => 'Для языка :locale обязательно заполните name и slug.',
    ),
    'city_category' => 
    array (
      'singular' => 'категория по городу',
      'plural' => 'категории по городам',
      'navigation' => 'Категории по городам',
      'active_locale' => 'Язык перевода',
      'active_locale_hint' => 'Выберите язык, поля которого нужно редактировать.',
      'translation_fields' => 'Поля перевода',
      'editing_translation' => 'Редактирование перевода: :locale',
      'default_locale_required' => 'Для языка :locale обязательно заполните name и slug.',
    ),
    'product_attribute' => 
    array (
      'singular' => 'атрибут',
      'plural' => 'атрибуты',
      'navigation' => 'Атрибуты',
      'active_locale' => 'Язык перевода',
      'active_locale_hint' => 'Выберите язык, поля которого нужно редактировать.',
      'translation_fields' => 'Поля перевода',
      'editing_translation' => 'Редактирование перевода: :locale',
      'default_locale_required' => 'Для языка :locale обязательно заполните name.',
    ),
    'product' => 
    array (
      'singular' => 'товар',
      'plural' => 'товары',
      'navigation' => 'Товары',
      'active_locale' => 'Язык перевода',
      'active_locale_hint' => 'Выберите язык, поля которого нужно редактировать.',
      'translation_fields' => 'Поля перевода',
      'editing_translation' => 'Редактирование перевода: :locale',
      'default_locale_required' => 'Для языка :locale обязательно заполните name и slug.',
    ),
    'product_review' => 
    array (
      'singular' => 'отзыв',
      'plural' => 'отзывы',
      'navigation' => 'Отзывы',
    ),
    'post' => 
    array (
      'singular' => 'запись',
      'plural' => 'записи',
      'navigation' => 'Записи',
      'default_locale_required' => 'Для языка :locale обязательно заполните title и slug.',
      'editor' => 
      array (
        'mode' => 
        array (
          'label' => 'Режим редактора',
          'visual' => 'Визуальный',
          'html' => 'HTML',
        ),
        'html_source' => 'HTML-код',
        'html_source_hint' => 'Редактируйте сырой HTML-код содержимого статьи.',
        'accent_quote' => 
        array (
          'label' => 'Акцентная цитата',
          'modal_heading' => 'Вставить акцентную цитату',
          'accent_text' => 'Акцентный текст',
          'body_text' => 'Основной текст',
          'preview_label' => 'Акцентная цитата: :text',
        ),
        'video_embed' => 
        array (
          'label' => 'Видео embed',
          'modal_heading' => 'Вставить видео',
          'url' => 'Ссылка на видео',
          'helper_text' => 'Вставьте ссылку YouTube или Vimeo.',
          'width' => 'Ширина видео',
          'width_helper_text' => 'Необязательно. Укажите максимальную ширину видео в пикселях.',
          'validation' => 'Используйте корректную ссылку YouTube или Vimeo.',
          'preview_label' => 'Встроенное видео: :provider',
          'iframe_title' => 'Видео :provider',
        ),
      ),
    ),
    'post_category' => 
    array (
      'singular' => 'категория записей',
      'plural' => 'категории записей',
      'navigation' => 'Категории записей',
      'active_locale' => 'Язык перевода',
      'active_locale_hint' => 'Выберите язык, поля которого нужно редактировать.',
      'translation_fields' => 'Поля перевода',
      'editing_translation' => 'Редактирование перевода: :locale',
      'default_locale_required' => 'Для языка :locale обязательно заполните name и slug.',
    ),
    'page' => 
    array (
      'singular' => 'страница',
      'plural' => 'страницы',
      'navigation' => 'Страницы',
      'active_locale' => 'Язык перевода',
      'active_locale_hint' => 'Выберите язык, поля которого нужно редактировать.',
      'translation_fields' => 'Поля перевода',
      'editing_translation' => 'Редактирование перевода: :locale',
      'default_locale_required' => 'Для языка :locale обязательно заполните title и slug.',
      'robots_default' => 'По умолчанию: index, follow',
    ),
    'order' => 
    array (
      'singular' => 'заказ',
      'plural' => 'заказы',
      'navigation' => 'Заказы',
    ),
    'order_status' => 
    array (
      'singular' => 'статус заказа',
      'plural' => 'статусы заказов',
      'navigation' => 'Статусы заказов',
      'default_locale_required' => 'Для языка :locale обязательно заполните name.',
      'color' => 'Цвет',
      'badge_background_color' => 'Цвет фона бейджа',
      'text_color' => 'Цвет текста',
      'is_default_for_new_order' => 'Статус по умолчанию',
      'is_default_for_new_order_hint' => 'Этот статус будет автоматически присвоен новым заказам',
      'delete_confirm' => 'Вы уверены, что хотите удалить этот статус заказа?',
    ),
    'payment_method' => 
    array (
      'singular' => 'способ оплаты',
      'plural' => 'способы оплаты',
      'navigation' => 'Способы оплаты',
      'default_locale_required' => 'Для языка :locale обязательно заполните name.',
    ),
    'shipping_method' => 
    array (
      'singular' => 'способ доставки',
      'plural' => 'способы доставки',
      'navigation' => 'Способы доставки',
      'default_locale_required' => 'Для языка :locale обязательно заполните name.',
    ),
    'site_setting' => 
    array (
      'singular' => 'настройка сайта',
      'plural' => 'настройки сайта',
      'navigation' => 'Настройки сайта',
    ),
    'currency' => 
    array (
      'singular' => 'валюта',
      'plural' => 'валюты',
      'navigation' => 'Валюты',
    ),
    'user' => 
    array (
      'singular' => 'пользователь',
      'plural' => 'пользователи',
      'navigation' => 'Пользователи',
      'first_name' => 'Имя',
      'last_name' => 'Фамилия',
      'phone' => 'Телефон',
      'password' => 'Пароль',
      'roles' => 'Роли',
      'email_verified_at' => 'Email подтвержден',
    ),
    'role' => 
    array (
      'singular' => 'роль',
      'plural' => 'роли',
      'navigation' => 'Роли и разрешения',
    ),
    'menu' => 
    array (
      'singular' => 'меню',
      'plural' => 'меню',
      'navigation' => 'Меню',
      'translation_fields' => 'Поля перевода',
      'editing_translation' => 'Редактирование перевода: :locale',
    ),
    'marketing_lead' => 
    array (
      'singular' => 'заявка',
      'plural' => 'заявки',
      'navigation' => 'Заявки',
    ),
  ),
  'menu' => 
  array (
    'tabs' => 
    array (
      'main' => 'Основное',
      'items' => 'Пункты меню',
    ),
    'items' => 'Пункты меню',
    'items_count' => 'Кол-во пунктов',
    'identifier_hint' => 'Системный ключ для быстрого получения меню в шаблонах, например: footer-information',
    'add_item' => 'Добавить пункт',
    'add_translation' => 'Добавить перевод',
    'open_in_new_tab' => 'Открыть в новой вкладке',
    'default_locale_required' => 'Для пунктов меню #:items обязательно добавьте перевод для языка :locale с label и URL.',
  ),
  'order' => 
  array (
    'order_section' => 'Заказ',
    'customer_section' => 'Данные покупателя',
    'user_section' => 'Авторизованный пользователь',
    'user' => 'Пользователь',
    'user_profile' => 'Профиль пользователя',
    'delivery_payment_section' => 'Доставка и оплата',
    'other_recipient_section' => 'Другой получатель',
    'has_other_recipient' => 'Получатель другой человек',
    'recipient_first_name' => 'Имя получателя',
    'recipient_last_name' => 'Фамилия получателя',
    'recipient_phone' => 'Телефон получателя',
    'recipient_email' => 'Email получателя',
    'number' => 'Номер заказа',
    'source' => 'Источник',
    'source_checkout' => 'Обычное оформление',
    'source_quick_order' => 'Быстрый заказ',
    'customer_name' => 'Имя клиента',
    'customer_phone' => 'Телефон',
    'customer_email' => 'Email',
    'comment' => 'Комментарий',
    'items' => 'Товары',
    'product' => 'Товар',
    'variant' => 'Вариация',
    'variant_attributes' => 'Свойства вариации',
    'quantity' => 'Количество',
    'thumbnail' => 'Миниатюра',
    'total_amount' => 'Итоговая сумма',
    'payment_method_code' => 'Код способа оплаты',
    'payment_method_name' => 'Название способа оплаты',
    'shipping_method_code' => 'Код способа доставки',
    'shipping_method_name' => 'Название способа доставки',
    'delivery_city_name' => 'Город доставки',
    'delivery_city_ref' => 'Ref города доставки',
    'delivery_warehouse_name' => 'Отделение доставки',
    'delivery_warehouse_ref' => 'Ref отделения доставки',
    'delivery_street' => 'Улица',
    'delivery_house' => 'Дом',
    'delivery_apartment' => 'Квартира',
    'status' => 
    array (
      'new' => 'Новый',
      'processing' => 'В обработке',
      'completed' => 'Завершен',
      'cancelled' => 'Отменен',
    ),
  ),
  'marketing_lead' => 
  array (
    'type' => 'Тип заявки',
    'name' => 'Имя',
    'subject' => 'Тема',
    'source_url' => 'URL страницы',
    'form_data' => 'Данные формы',
    'client_meta' => 'Метаданные клиента',
    'internal_note' => 'Внутренняя заметка',
    'processed_at' => 'Обработано',
    'types' => 
    array (
      'callback' => 'Обратный звонок',
      'contact_form' => 'Контактная форма',
      'product_waitlist' => 'Ожидание товара',
    ),
    'statuses' => 
    array (
      'new' => 'Новая',
      'processed' => 'Обработана',
    ),
  ),
  'site_setting' => 
  array (
    'general_section' => 'Общие настройки',
    'delivery_section' => 'Настройки доставки',
    'site_name' => 'Название сайта',
    'logo_path' => 'Логотип',
    'footer_logo_path' => 'Логотип футера',
    'favicon_svg_path' => 'Favicon SVG',
    'favicon_svg_path_hint' => 'Основной favicon в формате SVG.',
    'favicon_png_path' => 'Favicon PNG',
    'favicon_png_path_hint' => 'PNG fallback favicon для браузеров без поддержки SVG.',
    'nova_poshta_api_key' => 'API ключ Новой Почты',
    'nova_poshta_api_key_hint' => 'Используется для поиска городов и отделений на checkout.',
    'contacts' => 'Контакты',
    'social_links' => 'Соцсети',
    'contact_identifier_hint' => 'Уникальный ключ для шаблона, например: phone, address, email, working_hours',
    'social_identifier_hint' => 'Уникальный ключ для шаблона, например: instagram, facebook, telegram',
    'identifier_unique' => 'Идентификаторы должны быть уникальными. Дубликаты: :identifiers',
    'saved' => 'Настройки сохранены',
  ),
  'category' => 
  array (
    'tabs' => 
    array (
      'main' => 'Основное',
      'content' => 'Контент',
      'seo' => 'SEO',
    ),
    'actions' => 
    array (
      'view_on_site' => 'Открыть на сайте',
    ),
  ),
  'city_category' => 
  array (
    'display_category_ids' => 'Категории для показа',
  ),
  'product' => 
  array (
    'brand_id' => 'Бренд',
    'category_ids' => 'Категории',
    'tabs' => 
    array (
      'main' => 'Основное',
      'gallery' => 'Галерея',
      'additional' => 'Дополнительно',
      'characteristics' => 'Характеристики',
      'variants' => 'Вариации',
      'faq' => 'Вопросы и ответы',
      'relations' => 'Связи',
      'seo' => 'SEO',
    ),
    'gallery' => 
    array (
      'bulk_upload' => 'Пакетная загрузка изображений',
      'bulk_upload_hint' => 'Выберите несколько файлов, чтобы сразу добавить их в галерею товара.',
    ),
    'badges' => 
    array (
      'is_hit_sales' => 'Включить метку "Хит продаж"',
      'is_on_sale' => 'Включить метку "Акция"',
      'is_new' => 'Включить метку "Новинка"',
    ),
    'faq' => 
    array (
      'section_title' => 'Вопросы и ответы о товаре',
      'label' => 'Вопросы и ответы',
      'question' => 'Вопрос',
      'answer' => 'Ответ',
    ),
    'relations' => 
    array (
      'section_title' => 'Связанные товары',
      'color_related_product_ids' => 'Товар в другом цвете',
      'color_related_product_ids_hint' => 'Выбранные товары будут показаны на странице товара в блоке "Цвет".',
      'bought_together_product_ids' => 'С этим товаром покупают',
      'bought_together_product_ids_hint' => 'Выбранные товары будут показаны в блоке "Дополните образ".',
    ),
    'characteristics' => 
    array (
      'section_title' => 'Характеристики товара',
      'label' => 'Характеристики',
      'attribute' => 'Характеристика',
      'is_priority' => 'Приоритетная',
    ),
    'seo' => 
    array (
      'translations' => 'SEO переводы',
      'meta_title' => 'Meta Title',
      'meta_description' => 'Meta Description',
      'robots' => 'Robots',
      'robots_options' => 
      array (
        'index_follow' => 'Index, Follow',
        'noindex_follow' => 'Noindex, Follow',
        'index_nofollow' => 'Index, Nofollow',
        'noindex_nofollow' => 'Noindex, Nofollow',
      ),
    ),
    'variants' => 
    array (
      'section_title' => 'Варианты товара',
      'label' => 'Варианты',
      'old_price' => 'Старая цена',
      'attributes' => 'Атрибуты',
    ),
    'status' => 
    array (
      'draft' => 'Черновик',
      'published' => 'Опубликован',
    ),
    'type' => 
    array (
      'simple' => 'Простой',
      'variant' => 'Вариативный',
    ),
    'stock_status' => 
    array (
      'in_stock' => 'В наличии',
      'out_of_stock' => 'Нет в наличии',
      'preorder' => 'Под заказ',
    ),
    'actions' => 
    array (
      'view_on_site' => 'Открыть товар',
    ),
  ),
  'page' => 
  array (
    'actions' => 
    array (
      'view_on_site' => 'Открыть страницу',
    ),
  ),
  'post' => 
  array (
    'actions' => 
    array (
      'view_on_site' => 'Открыть запись',
    ),
  ),
  'product_attribute' => 
  array (
    'group_id' => 'Группа атрибутов',
    'value_type' => 'Тип значения',
    'is_filterable' => 'Использовать в фильтрах',
    'is_required' => 'Обязательный',
    'is_variant_axis' => 'Ось вариантов',
    'value_types' => 
    array (
      'string' => 'Текст',
      'text' => 'Текст',
      'integer' => 'Целое число',
      'numeric' => 'Десятичное число',
      'boolean' => 'Да/Нет',
      'select' => 'Список опций',
      'option' => 'Список опций',
    ),
  ),
  'content' => 
  array (
    'tabs' => 
    array (
      'main' => 'Основное',
      'content' => 'Контент',
      'design' => 'Дизайн',
      'seo' => 'SEO',
    ),
    'page_settings' => 'Настройки страницы',
    'page_background_desktop' => 'Цвет фона страницы desktop',
    'page_background_mobile' => 'Цвет фона страницы mobile',
    'show_breadcrumbs' => 'Показывать хлебные крошки',
    'show_title' => 'Показывать заголовок страницы',
    'add_block' => 'Добавить блок',
    'status' => 
    array (
      'draft' => 'Черновик',
      'published' => 'Опубликован',
    ),
    'seo' => 
    array (
      'translations' => 'SEO переводы',
      'meta_title' => 'Meta Title',
      'meta_description' => 'Meta Description',
      'robots' => 'Robots',
      'robots_options' => 
      array (
        'index_follow' => 'Index, Follow',
        'noindex_follow' => 'Noindex, Follow',
        'index_nofollow' => 'Index, Nofollow',
        'noindex_nofollow' => 'Noindex, Nofollow',
      ),
    ),
  ),
  'product_review' => 
  array (
    'display_name' => 'Имя автора',
    'product_link' => 'Связанный товар',
    'author_type' => 'Тип автора',
    'author_types' => 
    array (
      'guest' => 'Гость',
      'user' => 'Пользователь',
      'admin' => 'Администратор',
    ),
    'rating' => 'Оценка',
    'photos' => 'Фото',
    'photo' => 'Фото',
    'photo_alt' => 'Alt текст фото',
    'photos_count' => 'Кол-во фото',
    'replies_count' => 'Кол-во ответов',
    'created_at' => 'Создано',
    'add_reply' => 'Добавить ответ',
    'edit_reply' => 'Редактировать ответ',
    'status' => 
    array (
      'pending' => 'Ожидает модерации',
      'approved' => 'Одобрено',
      'rejected' => 'Отклонено',
    ),
    'actions' => 
    array (
      'approve' => 'Одобрить',
      'reject' => 'Отклонить',
      'approve_selected' => 'Одобрить выбранные',
      'reject_selected' => 'Отклонить выбранные',
      'view_reviews' => 'Отзывы',
    ),
  ),
  'locale_names' => 
  array (
    'uk' => 'Украинский',
    'ru' => 'Русский',
    'en' => 'English',
  ),
  'currency' => 
  array (
    'symbol' => 'Символ',
    'is_base' => 'Базовая валюта',
    'rate' => 'Курс к базовой',
  ),
);
