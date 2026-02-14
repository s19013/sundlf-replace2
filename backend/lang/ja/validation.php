<?php

return [

    /*
    |--------------------------------------------------------------------------
    | バリデーション言語行
    |--------------------------------------------------------------------------
    |
    | 以下の言語行にはバリデータクラスで使用されるデフォルトのエラーメッセージが
    | 含まれています。サイズルールなど、一部のルールには複数のバージョンがあります。
    | 各メッセージは自由に調整してください。
    |
    */

    'accepted' => ':attributeを承認してください。',
    'accepted_if' => ':otherが:valueの場合、:attributeを承認してください。',
    'active_url' => ':attributeには有効なURLを指定してください。',
    'after' => ':attributeには:dateより後の日付を指定してください。',
    'after_or_equal' => ':attributeには:date以降の日付を指定してください。',
    'alpha' => ':attributeには英字のみを含めてください。',
    'alpha_dash' => ':attributeには英字、数字、ダッシュ、アンダースコアのみを含めてください。',
    'alpha_num' => ':attributeには英字と数字のみを含めてください。',
    'any_of' => ':attributeが無効です。',
    'array' => ':attributeには配列を指定してください。',
    'ascii' => ':attributeには半角英数字と記号のみを含めてください。',
    'before' => ':attributeには:dateより前の日付を指定してください。',
    'before_or_equal' => ':attributeには:date以前の日付を指定してください。',
    'between' => [
        'array' => ':attributeには:min個から:max個の要素を指定してください。',
        'file' => ':attributeには:minKBから:maxKBまでのファイルを指定してください。',
        'numeric' => ':attributeには:minから:maxまでの数値を指定してください。',
        'string' => ':attributeには:min文字から:max文字までの文字列を指定してください。',
    ],
    'boolean' => ':attributeにはtrueまたはfalseを指定してください。',
    'can' => ':attributeに許可されていない値が含まれています。',
    'confirmed' => ':attributeの確認が一致しません。',
    'contains' => ':attributeに必須の値が含まれていません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attributeには有効な日付を指定してください。',
    'date_equals' => ':attributeには:dateと同じ日付を指定してください。',
    'date_format' => ':attributeは:formatの形式と一致しません。',
    'decimal' => ':attributeには小数点以下:decimal桁の数値を指定してください。',
    'declined' => ':attributeを拒否してください。',
    'declined_if' => ':otherが:valueの場合、:attributeを拒否してください。',
    'different' => ':attributeと:otherには異なる値を指定してください。',
    'digits' => ':attributeには:digits桁の数字を指定してください。',
    'digits_between' => ':attributeには:min桁から:max桁の数字を指定してください。',
    'dimensions' => ':attributeの画像サイズが無効です。',
    'distinct' => ':attributeに重複した値があります。',
    'doesnt_contain' => ':attributeに次の値を含めることはできません: :values',
    'doesnt_end_with' => ':attributeは次のいずれかで終わることはできません: :values',
    'doesnt_start_with' => ':attributeは次のいずれかで始まることはできません: :values',
    'email' => ':attributeには有効なメールアドレスを指定してください。',
    'encoding' => ':attributeは:encodingでエンコードされている必要があります。',
    'ends_with' => ':attributeは次のいずれかで終わる必要があります: :values',
    'enum' => '選択された:attributeは無効です。',
    'exists' => '選択された:attributeは無効です。',
    'extensions' => ':attributeには次のいずれかの拡張子が必要です: :values',
    'file' => ':attributeにはファイルを指定してください。',
    'filled' => ':attributeには値を指定してください。',
    'gt' => [
        'array' => ':attributeには:value個より多くの要素を指定してください。',
        'file' => ':attributeには:valueKBより大きいファイルを指定してください。',
        'numeric' => ':attributeには:valueより大きい値を指定してください。',
        'string' => ':attributeには:value文字より多い文字列を指定してください。',
    ],
    'gte' => [
        'array' => ':attributeには:value個以上の要素を指定してください。',
        'file' => ':attributeには:valueKB以上のファイルを指定してください。',
        'numeric' => ':attributeには:value以上の値を指定してください。',
        'string' => ':attributeには:value文字以上の文字列を指定してください。',
    ],
    'hex_color' => ':attributeには有効な16進数カラーコードを指定してください。',
    'image' => ':attributeには画像ファイルを指定してください。',
    'in' => '選択された:attributeは無効です。',
    'in_array' => ':attributeは:otherに存在する必要があります。',
    'in_array_keys' => ':attributeには次のキーのうち少なくとも1つを含める必要があります: :values',
    'integer' => ':attributeには整数を指定してください。',
    'ip' => ':attributeには有効なIPアドレスを指定してください。',
    'ipv4' => ':attributeには有効なIPv4アドレスを指定してください。',
    'ipv6' => ':attributeには有効なIPv6アドレスを指定してください。',
    'json' => ':attributeには有効なJSON文字列を指定してください。',
    'list' => ':attributeにはリストを指定してください。',
    'lowercase' => ':attributeには小文字を指定してください。',
    'lt' => [
        'array' => ':attributeには:value個より少ない要素を指定してください。',
        'file' => ':attributeには:valueKBより小さいファイルを指定してください。',
        'numeric' => ':attributeには:valueより小さい値を指定してください。',
        'string' => ':attributeには:value文字より少ない文字列を指定してください。',
    ],
    'lte' => [
        'array' => ':attributeには:value個以下の要素を指定してください。',
        'file' => ':attributeには:valueKB以下のファイルを指定してください。',
        'numeric' => ':attributeには:value以下の値を指定してください。',
        'string' => ':attributeには:value文字以下の文字列を指定してください。',
    ],
    'mac_address' => ':attributeには有効なMACアドレスを指定してください。',
    'max' => [
        'array' => ':attributeには:max個以下の要素を指定してください。',
        'file' => ':attributeには:maxKB以下のファイルを指定してください。',
        'numeric' => ':attributeには:max以下の値を指定してください。',
        'string' => ':attributeには:max文字以下の文字列を指定してください。',
    ],
    'max_digits' => ':attributeには:max桁以下の数字を指定してください。',
    'mimes' => ':attributeには次のファイルタイプを指定してください: :values',
    'mimetypes' => ':attributeには次のファイルタイプを指定してください: :values',
    'min' => [
        'array' => ':attributeには:min個以上の要素を指定してください。',
        'file' => ':attributeには:minKB以上のファイルを指定してください。',
        'numeric' => ':attributeには:min以上の値を指定してください。',
        'string' => ':attributeには:min文字以上の文字列を指定してください。',
    ],
    'min_digits' => ':attributeには:min桁以上の数字を指定してください。',
    'missing' => ':attributeは存在してはいけません。',
    'missing_if' => ':otherが:valueの場合、:attributeは存在してはいけません。',
    'missing_unless' => ':otherが:valueでない限り、:attributeは存在してはいけません。',
    'missing_with' => ':valuesが存在する場合、:attributeは存在してはいけません。',
    'missing_with_all' => ':valuesがすべて存在する場合、:attributeは存在してはいけません。',
    'multiple_of' => ':attributeには:valueの倍数を指定してください。',
    'not_in' => '選択された:attributeは無効です。',
    'not_regex' => ':attributeの形式が無効です。',
    'numeric' => ':attributeには数値を指定してください。',
    'password' => [
        'letters' => ':attributeには少なくとも1つの英字を含めてください。',
        'mixed' => ':attributeには少なくとも1つの大文字と1つの小文字を含めてください。',
        'numbers' => ':attributeには少なくとも1つの数字を含めてください。',
        'symbols' => ':attributeには少なくとも1つの記号を含めてください。',
        'uncompromised' => '指定された:attributeはデータ漏洩に含まれています。別の:attributeを選択してください。',
    ],
    'present' => ':attributeが存在する必要があります。',
    'present_if' => ':otherが:valueの場合、:attributeが存在する必要があります。',
    'present_unless' => ':otherが:valueでない限り、:attributeが存在する必要があります。',
    'present_with' => ':valuesが存在する場合、:attributeも存在する必要があります。',
    'present_with_all' => ':valuesがすべて存在する場合、:attributeも存在する必要があります。',
    'prohibited' => ':attributeは禁止されています。',
    'prohibited_if' => ':otherが:valueの場合、:attributeは禁止されています。',
    'prohibited_if_accepted' => ':otherが承認されている場合、:attributeは禁止されています。',
    'prohibited_if_declined' => ':otherが拒否されている場合、:attributeは禁止されています。',
    'prohibited_unless' => ':otherが:valuesに含まれていない限り、:attributeは禁止されています。',
    'prohibits' => ':attributeは:otherの存在を禁止しています。',
    'regex' => ':attributeの形式が無効です。',
    'required' => ':attributeは必須です。',
    'required_array_keys' => ':attributeには次のエントリが必要です: :values',
    'required_if' => ':otherが:valueの場合、:attributeは必須です。',
    'required_if_accepted' => ':otherが承認されている場合、:attributeは必須です。',
    'required_if_declined' => ':otherが拒否されている場合、:attributeは必須です。',
    'required_unless' => ':otherが:valuesに含まれていない限り、:attributeは必須です。',
    'required_with' => ':valuesが存在する場合、:attributeは必須です。',
    'required_with_all' => ':valuesがすべて存在する場合、:attributeは必須です。',
    'required_without' => ':valuesが存在しない場合、:attributeは必須です。',
    'required_without_all' => ':valuesがすべて存在しない場合、:attributeは必須です。',
    'same' => ':attributeと:otherは一致する必要があります。',
    'size' => [
        'array' => ':attributeには:size個の要素を含めてください。',
        'file' => ':attributeには:sizeKBのファイルを指定してください。',
        'numeric' => ':attributeには:sizeを指定してください。',
        'string' => ':attributeには:size文字の文字列を指定してください。',
    ],
    'starts_with' => ':attributeは次のいずれかで始まる必要があります: :values',
    'string' => ':attributeには文字列を指定してください。',
    'timezone' => ':attributeには有効なタイムゾーンを指定してください。',
    'unique' => ':attributeはすでに使用されています。',
    'uploaded' => ':attributeのアップロードに失敗しました。',
    'uppercase' => ':attributeには大文字を指定してください。',
    'url' => ':attributeには有効なURLを指定してください。',
    'ulid' => ':attributeには有効なULIDを指定してください。',
    'uuid' => ':attributeには有効なUUIDを指定してください。',

    /*
    |--------------------------------------------------------------------------
    | カスタムバリデーション言語行
    |--------------------------------------------------------------------------
    |
    | ここでは「属性名.ルール名」の規約を使用して、属性に対するカスタム
    | バリデーションメッセージを指定できます。これにより、特定の属性ルールに
    | 対するカスタム言語行を素早く指定できます。
    |
    */

    'custom' => [
        // 属性ごとのカスタムメッセージをここに追加
        // 例: 'email' => ['required' => 'メールアドレスを入力してください。'],
    ],

    /*
    |--------------------------------------------------------------------------
    | カスタムバリデーション属性
    |--------------------------------------------------------------------------
    |
    | 以下の言語行は、属性のプレースホルダーを「email」の代わりに
    | 「メールアドレス」のような、より読みやすい表現に置き換えるために
    | 使用されます。メッセージをより分かりやすくするのに役立ちます。
    |
    */

    'attributes' => require __DIR__.'/attributes.php',

];
