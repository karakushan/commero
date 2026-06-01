<?php

return array (
  'navigation' => 
  array (
    'catalog' => 'Каталог',
    'marketing' => 'Маркетинг',
    'orders' => 'Замовлення',
    'content' => 'Контент',
    'access' => 'Доступ',
    'payment_and_shipping' => 'Оплата і доставка',
    'system' => 'Система',
  ),
  'common' => 
  array (
    'id' => 'ID',
    'code' => 'Код',
    'name' => 'Назва',
    'title' => 'Заголовок',
    'slug' => 'Slug',
    'sort' => 'Сортування',
    'price' => 'Ціна',
    'availability' => 'Наявність',
    'status' => 'Статус',
    'type' => 'Тип',
    'description' => 'Опис',
    'excerpt' => 'Анонс',
    'content' => 'Контент',
    'full_description' => 'Повний опис',
    'locale' => 'Мова',
    'translations' => 'Переклади',
    'updated_at' => 'Оновлено',
    'created_at' => 'Створено',
    'published_at' => 'Дата публікації',
    'brand' => 'Бренд',
    'group' => 'Група',
    'options' => 'Опції',
    'label' => 'Підпис',
    'identifier' => 'Ідентифікатор',
    'value' => 'Значення',
    'path' => 'Шлях',
    'depth' => 'Рівень',
    'parent_category' => 'Батьківська категорія',
    'categories' => 'Категорії',
    'sku' => 'Артикул',
    'thumbnail' => 'Мініатюра',
    'icon' => 'Іконка',
    'is_active' => 'Активний',
    'url' => 'URL',
    'phone' => 'Телефон',
    'email' => 'Email',
    'message' => 'Повідомлення',
    'save' => 'Зберегти',
  ),
  'resources' => 
  array (
    'attribute_group' => 
    array (
      'singular' => 'група атрибутів',
      'plural' => 'групи атрибутів',
      'navigation' => 'Групи атрибутів',
    ),
    'brand' => 
    array (
      'singular' => 'бренд',
      'plural' => 'бренди',
      'navigation' => 'Бренди',
    ),
    'category' => 
    array (
      'singular' => 'категорія',
      'plural' => 'категорії',
      'navigation' => 'Категорії',
      'active_locale' => 'Мова перекладу',
      'active_locale_hint' => 'Оберіть мову, поля якої потрібно редагувати.',
      'translation_fields' => 'Поля перекладу',
      'editing_translation' => 'Редагування перекладу: :locale',
      'default_locale_required' => 'Для мови :locale обовʼязково заповніть name і slug.',
    ),
    'city_category' => 
    array (
      'singular' => 'категорія по місту',
      'plural' => 'категорії по містах',
      'navigation' => 'Категорії по містах',
      'active_locale' => 'Мова перекладу',
      'active_locale_hint' => 'Оберіть мову, поля якої потрібно редагувати.',
      'translation_fields' => 'Поля перекладу',
      'editing_translation' => 'Редагування перекладу: :locale',
      'default_locale_required' => 'Для мови :locale обовʼязково заповніть name і slug.',
    ),
    'product_attribute' => 
    array (
      'singular' => 'атрибут',
      'plural' => 'атрибути',
      'navigation' => 'Атрибути',
      'active_locale' => 'Мова перекладу',
      'active_locale_hint' => 'Оберіть мову, поля якої потрібно редагувати.',
      'translation_fields' => 'Поля перекладу',
      'editing_translation' => 'Редагування перекладу: :locale',
      'default_locale_required' => 'Для мови :locale обовʼязково заповніть name.',
    ),
    'product' => 
    array (
      'singular' => 'товар',
      'plural' => 'товари',
      'navigation' => 'Товари',
      'active_locale' => 'Мова перекладу',
      'active_locale_hint' => 'Оберіть мову, поля якої потрібно редагувати.',
      'translation_fields' => 'Поля перекладу',
      'editing_translation' => 'Редагування перекладу: :locale',
      'default_locale_required' => 'Для мови :locale обовʼязково заповніть name і slug.',
    ),
    'product_review' => 
    array (
      'singular' => 'відгук',
      'plural' => 'відгуки',
      'navigation' => 'Відгуки',
    ),
    'post' => 
    array (
      'singular' => 'запис',
      'plural' => 'записи',
      'navigation' => 'Записи',
      'default_locale_required' => 'Для мови :locale обовʼязково заповніть title і slug.',
      'editor' => 
      array (
        'mode' => 
        array (
          'label' => 'Режим редактора',
          'visual' => 'Візуальний',
          'html' => 'HTML',
        ),
        'html_source' => 'HTML-код',
        'html_source_hint' => 'Редагуйте сирий HTML-код контенту статті.',
        'accent_quote' => 
        array (
          'label' => 'Акцентна цитата',
          'modal_heading' => 'Вставити акцентну цитату',
          'accent_text' => 'Акцентний текст',
          'body_text' => 'Основний текст',
          'preview_label' => 'Акцентна цитата: :text',
        ),
        'video_embed' => 
        array (
          'label' => 'Відео embed',
          'modal_heading' => 'Вставити відео',
          'url' => 'Посилання на відео',
          'helper_text' => 'Вставте посилання YouTube або Vimeo.',
          'width' => 'Ширина відео',
          'width_helper_text' => 'Необовʼязково. Вкажіть максимальну ширину відео у пікселях.',
          'validation' => 'Використайте коректне посилання YouTube або Vimeo.',
          'preview_label' => 'Вбудоване відео: :provider',
          'iframe_title' => 'Відео :provider',
        ),
      ),
    ),
    'post_category' => 
    array (
      'singular' => 'категорія записів',
      'plural' => 'категорії записів',
      'navigation' => 'Категорії записів',
      'active_locale' => 'Мова перекладу',
      'active_locale_hint' => 'Оберіть мову, поля якої потрібно редагувати.',
      'translation_fields' => 'Поля перекладу',
      'editing_translation' => 'Редагування перекладу: :locale',
      'default_locale_required' => 'Для мови :locale обовʼязково заповніть name і slug.',
    ),
    'page' => 
    array (
      'singular' => 'сторінка',
      'plural' => 'сторінки',
      'navigation' => 'Сторінки',
      'active_locale' => 'Мова перекладу',
      'active_locale_hint' => 'Оберіть мову, поля якої потрібно редагувати.',
      'translation_fields' => 'Поля перекладу',
      'editing_translation' => 'Редагування перекладу: :locale',
      'default_locale_required' => 'Для мови :locale обовʼязково заповніть title і slug.',
      'robots_default' => 'За замовчуванням: index, follow',
    ),
    'order' => 
    array (
      'singular' => 'замовлення',
      'plural' => 'замовлення',
      'navigation' => 'Замовлення',
    ),
    'order_status' => 
    array (
      'singular' => 'статус замовлення',
      'plural' => 'статуси замовлення',
      'navigation' => 'Статуси замовлень',
      'default_locale_required' => 'Для мови :locale обовʼязково заповніть name.',
      'color' => 'Колір',
      'badge_background_color' => 'Колір фону бейджа',
      'text_color' => 'Колір тексту',
      'is_default_for_new_order' => 'Статус за замовчуванням',
      'is_default_for_new_order_hint' => 'Цей статус буде автоматично присвоєний новим замовленням',
      'delete_confirm' => 'Ви впевнені, що хочете видалити цей статус замовлення?',
    ),
    'payment_method' => 
    array (
      'singular' => 'спосіб оплати',
      'plural' => 'способи оплати',
      'navigation' => 'Способи оплати',
      'default_locale_required' => 'Для мови :locale обовʼязково заповніть name.',
    ),
    'shipping_method' => 
    array (
      'singular' => 'спосіб доставки',
      'plural' => 'способи доставки',
      'navigation' => 'Способи доставки',
      'default_locale_required' => 'Для мови :locale обовʼязково заповніть name.',
    ),
    'site_setting' => 
    array (
      'singular' => 'налаштування сайту',
      'plural' => 'налаштування сайту',
      'navigation' => 'Налаштування сайту',
    ),
    'currency' => 
    array (
      'singular' => 'валюта',
      'plural' => 'валюти',
      'navigation' => 'Валюти',
    ),
    'user' => 
    array (
      'singular' => 'користувач',
      'plural' => 'користувачі',
      'navigation' => 'Користувачі',
      'first_name' => 'Ім\'я',
      'last_name' => 'Прізвище',
      'phone' => 'Телефон',
      'password' => 'Пароль',
      'roles' => 'Ролі',
      'email_verified_at' => 'Email підтверджено',
    ),
    'role' => 
    array (
      'singular' => 'роль',
      'plural' => 'ролі',
      'navigation' => 'Ролі та дозволи',
    ),
    'menu' => 
    array (
      'singular' => 'меню',
      'plural' => 'меню',
      'navigation' => 'Меню',
      'translation_fields' => 'Поля перекладу',
      'editing_translation' => 'Редагування перекладу: :locale',
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
      'main' => 'Основне',
      'items' => 'Пункти меню',
    ),
    'items' => 'Пункти меню',
    'items_count' => 'К-сть пунктів',
    'identifier_hint' => 'Системний ключ для швидкого отримання меню у шаблонах, наприклад: footer-information',
    'add_item' => 'Додати пункт меню',
    'add_translation' => 'Додати переклад',
    'open_in_new_tab' => 'Відкрити в новій вкладці',
    'default_locale_required' => 'Для пунктів меню #:items обовʼязково додайте переклад для мови :locale з label та URL.',
  ),
  'order' => 
  array (
    'order_section' => 'Замовлення',
    'customer_section' => 'Дані покупця',
    'user_section' => 'Авторизований користувач',
    'user' => 'Користувач',
    'user_profile' => 'Профіль користувача',
    'delivery_payment_section' => 'Доставка та оплата',
    'other_recipient_section' => 'Інший отримувач',
    'has_other_recipient' => 'Отримувач інша людина',
    'recipient_first_name' => 'Ім\'я отримувача',
    'recipient_last_name' => 'Прізвище отримувача',
    'recipient_phone' => 'Телефон отримувача',
    'recipient_email' => 'Email отримувача',
    'number' => 'Номер замовлення',
    'source' => 'Джерело',
    'source_checkout' => 'Звичайне оформлення',
    'source_quick_order' => 'Швидке замовлення',
    'customer_name' => 'Ім\'я клієнта',
    'customer_phone' => 'Телефон',
    'customer_email' => 'Email',
    'comment' => 'Коментар',
    'items' => 'Товари',
    'product' => 'Товар',
    'variant' => 'Варіація',
    'variant_attributes' => 'Властивості варіації',
    'quantity' => 'Кількість',
    'thumbnail' => 'Мініатюра',
    'total_amount' => 'Підсумкова сума',
    'payment_method_code' => 'Код способу оплати',
    'payment_method_name' => 'Назва способу оплати',
    'shipping_method_code' => 'Код способу доставки',
    'shipping_method_name' => 'Назва способу доставки',
    'delivery_city_name' => 'Місто доставки',
    'delivery_city_ref' => 'Ref міста доставки',
    'delivery_warehouse_name' => 'Відділення доставки',
    'delivery_warehouse_ref' => 'Ref відділення доставки',
    'delivery_street' => 'Вулиця',
    'delivery_house' => 'Будинок',
    'delivery_apartment' => 'Квартира',
    'status' => 
    array (
      'new' => 'Нове',
      'processing' => 'В обробці',
      'completed' => 'Завершене',
      'cancelled' => 'Скасоване',
    ),
  ),
  'marketing_lead' => 
  array (
    'type' => 'Тип заявки',
    'name' => 'Ім\'я',
    'subject' => 'Тема',
    'source_url' => 'URL сторінки',
    'form_data' => 'Дані форми',
    'client_meta' => 'Метадані клієнта',
    'internal_note' => 'Внутрішня нотатка',
    'processed_at' => 'Опрацьовано',
    'types' => 
    array (
      'callback' => 'Зворотний дзвінок',
      'contact_form' => 'Контактна форма',
      'product_waitlist' => 'Очікування товару',
    ),
    'statuses' => 
    array (
      'new' => 'Нова',
      'processed' => 'Опрацьована',
    ),
  ),
  'site_setting' => 
  array (
    'general_section' => 'Загальні налаштування',
    'delivery_section' => 'Налаштування доставки',
    'site_name' => 'Назва сайту',
    'logo_path' => 'Логотип',
    'footer_logo_path' => 'Логотип футера',
    'favicon_svg_path' => 'Favicon SVG',
    'favicon_svg_path_hint' => 'Основний favicon у форматі SVG.',
    'favicon_png_path' => 'Favicon PNG',
    'favicon_png_path_hint' => 'PNG favicon для браузерів без підтримки SVG.',
    'nova_poshta_api_key' => 'API ключ Нової Пошти',
    'nova_poshta_api_key_hint' => 'Ключ використовується для пошуку міст та відділень на checkout.',
    'contacts' => 'Контакти',
    'social_links' => 'Соцмережі',
    'contact_identifier_hint' => 'Унікальний ключ для шаблону, наприклад: phone, address, email, working_hours',
    'social_identifier_hint' => 'Унікальний ключ для шаблону, наприклад: instagram, facebook, telegram',
    'identifier_unique' => 'Ідентифікатори мають бути унікальними. Дублікати: :identifiers',
    'saved' => 'Налаштування збережено',
  ),
  'category' => 
  array (
    'tabs' => 
    array (
      'main' => 'Основне',
      'content' => 'Контент',
      'seo' => 'SEO',
    ),
    'actions' => 
    array (
      'view_on_site' => 'Переглянути на сайті',
    ),
  ),
  'city_category' => 
  array (
    'display_category_ids' => 'Категорії для показу',
  ),
  'product' => 
  array (
    'brand_id' => 'Бренд',
    'category_ids' => 'Категорії',
    'tabs' => 
    array (
      'main' => 'Основне',
      'gallery' => 'Галерея',
      'additional' => 'Додатково',
      'characteristics' => 'Характеристики',
      'variants' => 'Варіації',
      'faq' => 'Питання та відповіді',
      'relations' => 'Звʼязки',
      'seo' => 'SEO',
    ),
    'gallery' => 
    array (
      'bulk_upload' => 'Пакетне завантаження зображень',
      'bulk_upload_hint' => 'Виберіть кілька файлів, щоб одразу додати їх у галерею товару.',
    ),
    'badges' => 
    array (
      'is_hit_sales' => 'Увімкнути мітку "Хіт продаж"',
      'is_on_sale' => 'Увімкнути мітку "Акція"',
      'is_new' => 'Увімкнути мітку "Новинка"',
    ),
    'faq' => 
    array (
      'section_title' => 'Питання та відповіді товару',
      'label' => 'Питання та відповіді',
      'question' => 'Питання',
      'answer' => 'Відповідь',
    ),
    'relations' => 
    array (
      'section_title' => 'Звʼязані товари',
      'color_related_product_ids' => 'Товар в іншому кольорі',
      'color_related_product_ids_hint' => 'Вибрані товари будуть показані на сторінці товару в блоці "Колір".',
      'bought_together_product_ids' => 'З цим товаром купують',
      'bought_together_product_ids_hint' => 'Вибрані товари будуть показані в блоці "Доповни свій образ".',
    ),
    'characteristics' => 
    array (
      'section_title' => 'Характеристики товару',
      'label' => 'Характеристики',
      'attribute' => 'Характеристика',
      'is_priority' => 'Пріоритетна',
    ),
    'seo' => 
    array (
      'translations' => 'SEO переклади',
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
      'section_title' => 'Варіанти товару',
      'label' => 'Варіанти',
      'old_price' => 'Стара ціна',
      'attributes' => 'Атрибути',
    ),
    'status' => 
    array (
      'draft' => 'Чернетка',
      'published' => 'Опубліковано',
    ),
    'type' => 
    array (
      'simple' => 'Простий',
      'variant' => 'Варіативний',
    ),
    'stock_status' => 
    array (
      'in_stock' => 'В наявності',
      'out_of_stock' => 'Немає в наявності',
      'preorder' => 'Під замовлення',
    ),
    'actions' => 
    array (
      'view_on_site' => 'Відкрити товар',
    ),
  ),
  'page' => 
  array (
    'actions' => 
    array (
      'view_on_site' => 'Відкрити сторінку',
    ),
  ),
  'post' => 
  array (
    'actions' => 
    array (
      'view_on_site' => 'Відкрити запис',
    ),
  ),
  'product_attribute' => 
  array (
    'group_id' => 'Група атрибутів',
    'value_type' => 'Тип значення',
    'is_filterable' => 'Використовувати у фільтрах',
    'is_required' => 'Обов\'язковий',
    'is_variant_axis' => 'Вісь варіантів',
    'value_types' => 
    array (
      'string' => 'Текст',
      'text' => 'Текст',
      'integer' => 'Ціле число',
      'numeric' => 'Десяткове число',
      'boolean' => 'Так/Ні',
      'select' => 'Список опцій',
      'option' => 'Список опцій',
    ),
  ),
  'content' => 
  array (
    'tabs' => 
    array (
      'main' => 'Основне',
      'content' => 'Контент',
      'design' => 'Дизайн',
      'seo' => 'SEO',
    ),
    'page_settings' => 'Налаштування сторінки',
    'page_background_desktop' => 'Колір фону сторінки desktop',
    'page_background_mobile' => 'Колір фону сторінки mobile',
    'show_breadcrumbs' => 'Показувати хлібні крихти',
    'show_title' => 'Показувати заголовок сторінки',
    'add_block' => 'Додати блок',
    'status' => 
    array (
      'draft' => 'Чернетка',
      'published' => 'Опубліковано',
    ),
    'seo' => 
    array (
      'translations' => 'SEO переклади',
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
    'display_name' => 'Ім\'я автора',
    'product_link' => 'Пов’язаний товар',
    'author_type' => 'Тип автора',
    'author_types' => 
    array (
      'guest' => 'Гість',
      'user' => 'Користувач',
      'admin' => 'Адміністратор',
    ),
    'rating' => 'Оцінка',
    'photos' => 'Фото',
    'photo' => 'Фото',
    'photo_alt' => 'Alt текст фото',
    'photos_count' => 'К-сть фото',
    'replies_count' => 'К-сть відповідей',
    'created_at' => 'Створено',
    'add_reply' => 'Додати відповідь',
    'edit_reply' => 'Редагувати відповідь',
    'status' => 
    array (
      'pending' => 'Очікує модерації',
      'approved' => 'Схвалено',
      'rejected' => 'Відхилено',
    ),
    'actions' => 
    array (
      'approve' => 'Схвалити',
      'reject' => 'Відхилити',
      'approve_selected' => 'Схвалити вибрані',
      'reject_selected' => 'Відхилити вибрані',
      'view_reviews' => 'Відгуки',
    ),
  ),
  'locale_names' => 
  array (
    'uk' => 'Українська',
    'ru' => 'Російська',
    'en' => 'English',
  ),
  'currency' => 
  array (
    'symbol' => 'Символ',
    'is_base' => 'Базова валюта',
    'rate' => 'Курс до базової',
  ),
);
