<?php

/*
|--------------------------------------------------------------------------
| Mensajes de validación en español
|--------------------------------------------------------------------------
|
| Estos textos los ve la persona equivocándose al usar la herramienta: al
| registrarse, al registrar cómo se siente, al autorizar a un profesional.
| Se escriben en segunda persona y en palabras llanas, sin jerga técnica, y
| siempre dicen qué hacer para arreglarlo.
|
| Este archivo sustituye al de inglés que Laravel trae por defecto. Antes de
| publicarlo, un alta fallida mostraba literalmente "validation.confirmed" o
| "validation.integer": no informaba de nada.
|
| Convención: los mensajes NO empiezan por el nombre del campo, porque en
| español el atributo va en minúsculas ("la edad") y una oración no puede
| empezar así. Por eso se constructions "Falta :attribute", "El valor de
| :attribute ..." o "Revisa :attribute".
|
*/

return [

    'accepted' => 'Necesitas aceptar :attribute para continuar.',
    'accepted_if' => 'Necesitas aceptar :attribute cuando :other es :value.',
    'active_url' => 'Revisa :attribute: no parece una dirección web válida.',
    'after' => 'El valor de :attribute tiene que ser una fecha posterior a :date.',
    'after_or_equal' => 'El valor de :attribute tiene que ser una fecha igual o posterior a :date.',
    'alpha' => 'El valor de :attribute solo puede llevar letras.',
    'alpha_dash' => 'El valor de :attribute solo puede llevar letras, números y guiones.',
    'alpha_num' => 'El valor de :attribute solo puede llevar letras y números.',
    'any_of' => 'Falta elegir uno de estos campos: :values.',
    'array' => 'Revisa :attribute: tiene que ser una lista.',
    'ascii' => 'El valor de :attribute solo puede llevar letras y símbolos simples.',
    'before' => 'El valor de :attribute tiene que ser una fecha anterior a :date.',
    'before_or_equal' => 'El valor de :attribute tiene que ser una fecha igual o anterior a :date.',
    'between' => [
        'array' => 'El valor de :attribute tiene que llevar entre :min y :max elementos.',
        'file' => 'El archivo de :attribute tiene que pesar entre :min y :max kilobytes.',
        'numeric' => 'El valor de :attribute tiene que estar entre :min y :max.',
        'string' => 'El valor de :attribute tiene que tener entre :min y :max caracteres.',
    ],
    'boolean' => 'El valor de :attribute solo puede ser sí o no.',
    'can' => 'Ese valor de :attribute no está permitido.',
    'confirmed' => 'Las dos contraseñas no coinciden. Vuelve a escribirlas.',
    'contains' => 'Falta un valor obligatorio en :attribute.',
    'current_password' => 'La contraseña es incorrecta.',
    'date' => 'El valor de :attribute no es una fecha válida.',
    'date_equals' => 'El valor de :attribute tiene que ser la fecha :date.',
    'date_format' => 'El valor de :attribute no tiene el formato :format.',
    'decimal' => 'El valor de :attribute tiene que tener :decimal decimales.',
    'declined' => 'Has rechazado :attribute.',
    'declined_if' => 'Tienes que rechazar :attribute cuando :other es :value.',
    'different' => 'El valor de :attribute y el de :other tienen que ser distintos.',
    'digits' => 'El valor de :attribute tiene que tener :digits dígitos.',
    'digits_between' => 'El valor de :attribute tiene que tener entre :min y :max dígitos.',
    'dimensions' => 'Revisa la imagen de :attribute: sus dimensiones no son válidas.',
    'distinct' => 'Hay valores repetidos en :attribute.',
    'doesnt_end_with' => 'El valor de :attribute no puede terminar con: :values.',
    'doesnt_start_with' => 'El valor de :attribute no puede empezar por: :values.',
    'email' => 'Revisa :attribute: no parece una dirección de correo válida.',
    'ends_with' => 'El valor de :attribute tiene que terminar con: :values.',
    'enum' => 'El valor de :attribute no es válido.',
    'exists' => 'Ese :attribute no existe.',
    'extensions' => 'El archivo de :attribute tiene que ser de tipo: :values.',
    'file' => 'Revisa :attribute: tiene que ser un archivo.',
    'filled' => 'No puedes dejar vacío :attribute.',
    'gt' => [
        'array' => 'El valor de :attribute tiene que tener más de :value elementos.',
        'file' => 'El archivo de :attribute tiene que pesar más de :value kilobytes.',
        'numeric' => 'El valor de :attribute tiene que ser mayor que :value.',
        'string' => 'El valor de :attribute tiene que tener más de :value caracteres.',
    ],
    'gte' => [
        'array' => 'El valor de :attribute tiene que tener :value elementos o más.',
        'file' => 'El archivo de :attribute tiene que pesar :value kilobytes o más.',
        'numeric' => 'El valor de :attribute tiene que ser :value o mayor.',
        'string' => 'El valor de :attribute tiene que tener :value caracteres o más.',
    ],
    'image' => 'El archivo de :attribute tiene que ser una imagen.',
    'in' => 'El valor de :attribute no es válido.',
    'integer' => 'El valor de :attribute tiene que ser un número entero.',
    'ip' => 'Revisa :attribute: no parece una dirección IP válida.',
    'ipv4' => 'Revisa :attribute: no parece una dirección IPv4 válida.',
    'ipv6' => 'Revisa :attribute: no parece una dirección IPv6 válida.',
    'json' => 'Revisa :attribute: no tiene el formato de texto esperado.',
    'list' => 'Revisa :attribute: tiene que ser una lista.',
    'lowercase' => 'El valor de :attribute tiene que ir en minúsculas.',
    'lt' => [
        'array' => 'El valor de :attribute tiene que tener menos de :value elementos.',
        'file' => 'El archivo de :attribute tiene que pesar menos de :value kilobytes.',
        'numeric' => 'El valor de :attribute tiene que ser menor que :value.',
        'string' => 'El valor de :attribute tiene que tener menos de :value caracteres.',
    ],
    'lte' => [
        'array' => 'El valor de :attribute no puede tener más de :value elementos.',
        'file' => 'El archivo de :attribute no puede pesar más de :value kilobytes.',
        'numeric' => 'El valor de :attribute tiene que ser :value o menor.',
        'string' => 'El valor de :attribute no puede tener más de :value caracteres.',
    ],
    'mac_address' => 'Revisa :attribute: no parece una dirección MAC válida.',
    'max' => [
        'array' => 'El valor de :attribute no puede tener más de :max elementos.',
        'file' => 'El archivo de :attribute no puede pesar más de :max kilobytes.',
        'numeric' => 'El valor de :attribute no puede ser mayor que :max.',
        'string' => 'El valor de :attribute es demasiado largo: máximo :max caracteres.',
    ],
    'max_digits' => 'El valor de :attribute no puede tener más de :max dígitos.',
    'mimes' => 'El archivo de :attribute tiene que ser de tipo: :values.',
    'mimetypes' => 'El archivo de :attribute tiene que ser de tipo: :values.',
    'min' => [
        'array' => 'El valor de :attribute tiene que tener al menos :min elementos.',
        'file' => 'El archivo de :attribute tiene que pesar al menos :min kilobytes.',
        'numeric' => 'El valor de :attribute tiene que ser :min o más.',
        'string' => 'El valor de :attribute es demasiado corto: mínimo :min caracteres.',
    ],
    'min_digits' => 'El valor de :attribute tiene que tener al menos :min dígitos.',
    'missing' => 'Falta el campo de :attribute.',
    'multiple_of' => 'El valor de :attribute tiene que ser múltiplo de :value.',
    'not_in' => 'El valor de :attribute no es válido.',
    'not_regex' => 'Revisa :attribute: el formato no es válido.',
    'numeric' => 'El valor de :attribute tiene que ser un número.',
    'present' => 'Falta el campo de :attribute.',
    'prohibited' => 'No puedes enviar :attribute.',
    'prohibited_if' => 'No puedes enviar :attribute cuando :other es :value.',
    'prohibited_if_accepted' => 'No puedes enviar :attribute cuando :other está aceptado.',
    'prohibited_if_declined' => 'No puedes enviar :attribute cuando :other está rechazado.',
    'prohibited_unless' => 'No puedes enviar :attribute salvo que :other esté en :values.',
    'prohibits' => 'No puedes enviar :attribute junto con :other.',
    'regex' => 'Revisa :attribute: el formato no es válido.',
    'required' => 'Falta :attribute.',
    'required_array_keys' => 'A :attribute le faltan estas claves: :values.',
    'required_if' => 'Falta :attribute cuando :other es :value.',
    'required_if_accepted' => 'Falta :attribute cuando :other está aceptado.',
    'required_if_declined' => 'Falta :attribute cuando :other está rechazado.',
    'required_unless' => 'Falta :attribute salvo que :other esté en :values.',
    'required_with' => 'Falta :attribute cuando también hay :values.',
    'required_with_all' => 'Falta :attribute cuando están :values.',
    'required_without' => 'Falta :attribute cuando no hay :values.',
    'required_without_all' => 'Falta :attribute cuando no hay ninguno de :values.',
    'same' => 'El valor de :attribute tiene que coincidir con el de :other.',
    'size' => [
        'array' => 'El valor de :attribute tiene que tener :size elementos.',
        'file' => 'El archivo de :attribute tiene que pesar :size kilobytes.',
        'numeric' => 'El valor de :attribute tiene que ser :size.',
        'string' => 'El valor de :attribute tiene que tener :size caracteres.',
    ],
    'starts_with' => 'El valor de :attribute tiene que empezar por: :values.',
    'string' => 'Revisa :attribute: tiene que ser texto.',
    'timezone' => 'Revisa :attribute: no parece una zona horaria válida.',
    'unique' => 'El valor de :attribute ya está en uso. Prueba con otro.',
    'uploaded' => 'No se pudo subir :attribute.',
    'uppercase' => 'El valor de :attribute tiene que ir en mayúsculas.',
    'url' => 'Revisa :attribute: no parece una dirección web válida.',
    'ulid' => 'Revisa :attribute: el identificador no es válido.',
    'uuid' => 'Revisa :attribute: el identificador no es válido.',

    /*
    |--------------------------------------------------------------------------
    | Mensajes propios de este proyecto
    |--------------------------------------------------------------------------
    */

    'custom' => [

        // El código de autorización es el paso que hacía fallar el alta de los
        // dos tipos de cuenta que no son estudiante, así que el mensaje dice
        // qué hacer: escribirlo, o elegir la otra opción.
        'admin_token' => [
            'required' => 'Esta cuenta necesita el código de autorización. Escríbelo aquí, o elige "Soy estudiante" si te has equivocado de opción.',
            'string' => 'Revisa el código de autorización: no tiene el formato correcto.',
            'max' => 'El código de autorización es demasiado largo.',
        ],

        'rol' => [
            'in' => 'Ese tipo de cuenta no existe.',
            'required' => 'Elige qué tipo de cuenta vas a crear.',
        ],

        'edad' => [
            'min' => 'Esta herramienta es para mayores de 15 años.',
            'max' => 'Revisa la edad: el máximo es 99 años.',
            'integer' => 'La edad tiene que ser un número entero, sin letras ni comas.',
            'numeric' => 'La edad tiene que ser un número.',
            'required' => 'Falta tu edad.',
        ],

        'nombre' => [
            'required' => 'Falta tu nombre.',
            'max' => 'El nombre es demasiado largo: máximo 100 caracteres.',
        ],

        'correo' => [
            'required' => 'Falta el correo electrónico.',
            'email' => 'Revisa el correo: no parece una dirección válida.',
            'unique' => 'Ya existe una cuenta con ese correo. Si ya te registraste, entra con tu contraseña.',
            'max' => 'El correo es demasiado largo.',
        ],

        'contrasena' => [
            'required' => 'Falta la contraseña.',
            'min' => 'La contraseña es demasiado corta: mínimo 8 caracteres.',
            'confirmed' => 'Las dos contraseñas no coinciden. Vuelve a escribirlas.',
        ],

        'contrasena_confirmation' => [
            'required' => 'Repite la contraseña para confirmar que es la correcta.',
        ],

        'energia' => [
            'min' => 'La energía va de 1 a 100.',
            'max' => 'La energía va de 1 a 100.',
            'integer' => 'La energía tiene que ser un número entero.',
            'required' => 'Indica tu nivel de energía.',
        ],

        'emocion' => [
            'required' => 'Elige cómo te sientes.',
            'in' => 'Esa emoción no está en la lista.',
        ],

        'observaciones' => [
            'max' => 'La nota es demasiado larga: máximo 1000 caracteres.',
        ],

        'codigo' => [
            'required' => 'Escribe el código que te dio tu profesional.',
            'max' => 'Ese código es demasiado largo.',
        ],

        'alcance' => [
            'required' => 'Elige al menos una cosa que quieras compartir.',
            'array' => 'Elige al menos una cosa que quieras compartir.',
            'min' => 'Elige al menos una cosa que quieras compartir.',
        ],

        'motivo' => [
            'max' => 'El motivo es demasiado largo: máximo 500 caracteres.',
        ],

        'avatar' => [
            'image' => 'El archivo tiene que ser una imagen.',
            'mimes' => 'La foto tiene que ser un JPG, PNG o GIF.',
            'max' => 'La foto es demasiado grande: máximo 2 MB.',
        ],

        'tema' => [
            'in' => 'Ese tema no existe.',
        ],

        'mensaje' => [
            'min' => 'El mensaje es demasiado corto.',
            'max' => 'El mensaje es demasiado largo: máximo 1000 caracteres.',
        ],

        'ids' => [
            'required' => 'No has seleccionado nada.',
            'array' => 'La selección no es válida.',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Nombres de los campos
    |--------------------------------------------------------------------------
    |
    | Sin esto Laravel pondría el nombre técnico del campo en el mensaje
    | ("El campo contrasena debe..."). Aquí cada campo se llama como lo
    | entiende la persona, en minúsculas porque va dentro de la frase.
    |
    */

    'attributes' => [
        'nombre' => 'tu nombre',
        'edad' => 'la edad',
        'correo' => 'el correo electrónico',
        'contrasena' => 'la contraseña',
        'contrasena_confirmation' => 'la verificación de la contraseña',
        'password' => 'la contraseña',
        'rol' => 'el tipo de cuenta',
        'admin_token' => 'el código de autorización',

        'emocion' => 'la emoción',
        'energia' => 'la energía',
        'observaciones' => 'la nota',
        'contexto' => 'el contexto',
        'avatar' => 'la foto',
        'tema' => 'el tema',
        'estado' => 'el estado',

        'codigo' => 'el código',
        'alcance' => 'lo que quieres compartir',
        'motivo' => 'el motivo',

        'ids' => 'la selección',
        'mensaje' => 'el mensaje',
    ],

];
