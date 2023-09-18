# projet-n-2-php

POSTMAN :

php-dev-2.online/api/v1/addTech :

{

    "techName": "New Tech",
    "logoURL": "http://example.com/logo.png",
    "categories[]": 1,
    "categories[]": 4

}

php-dev-2.online/api/v1/addCategory :

{

    "categoryName": "New Category"

}



php-dev-2.online/api/v1/modCategory :

{

    "categoryID": 1,
    "newCategoryName": "Updated Category"

}

php-dev-2.online/api/v1/addResource :

{

    "techID": 1,
    "resourceName": "Resource Name",
    "resourceURL": "http://example.com/resource"

}

php-dev-2.online/api/v1/getCategory :

{
        "ID": 1,
        "name": "Langages de programmation2",
        "technologies": [
            {
                "ID": 23,
                "name": "html",
                "logoURL": "logo/html-logo.png"
            }
        ]
    },
    {
        "ID": 2,
        "name": "Communaute et ressources dapprentissage",
        "technologies": []
    },
    {
        "ID": 3,
        "name": "Tendances et nouveautes",
        "technologies": []
    },
    {
        "ID": 4,
        "name": "Outils de developpement",
        "technologies": [
            {
                "ID": 23,
                "name": "html",
                "logoURL": "logo/html-logo.png"
            }
        ]
    },
    {
        "ID": 5,
        "name": "Developpement mobile et responsive",
        "technologies": []
    },
    {
        "ID": 6,
        "name": "Deploiement et gestion de serveurs",
        "technologies": []
    },
    {
        "ID": 7,
        "name": "Securite web",
        "technologies": []
    },
    {
        "ID": 8,
        "name": "Developpement back-end",
        "technologies": []
    },
    {
    "ID": 9,
    "name": "Developpement frontal",
    "technologies": [
            
    {

    "ID": 23,
     "name": "html",
    "logoURL": "logo/html-logo.png"

    }
        ]
},

{

    "ID": 10,
    "name": "Base de donnees",
    "technologies": []

},

{

    "ID": 11,
    "name": "Frameworks et bibliotheques",
    "technologies": []
}


php-dev-2.online/api/v1/deleteCategory : 

{

    "categoryID": 1,

}


php-dev-2.online/api/v1/getResource : 

{
    [
        {
            "id": 3,
            "description": "HTML (HyperText Markup Language)",
            "url": "https://developer.mozilla.org/fr/docs/Web/HTML"
        }
    ]
}

php-dev-2.online/api/v1/getTech : 

{
    [
        {
            "ID": 23,
            "name": "html",
            "logoURL": "logo/html-logo.png",
            "categories": "Langages de programmation2,Outils de developpement,Developpement frontal",
            "resource_urls": "https://developer.mozilla.org/fr/docs/Web/HTML,https://developer.mozilla.org/fr/docs/Web/HTML,https://developer.mozilla.org/fr/docs/Web/HTML"
        }
    ]
}