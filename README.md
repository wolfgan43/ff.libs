#Server

| Software |       Version   | Required |
|----------|-----------------|:-------: |
| Apache   |     Last Stable |    Yes   |
| Php      | 7.x Last Stable |    Yes   |
| MySql    |     Last Stable |    Yes   |
| MongoDB  |     Last Stable |    Yes   |
| Redis    |     Last Stable |    No    |
| Convert  |     Last Stable |    No    |


## DNS settings
Set SPF record (Sender Policy Framework)

##Apache settings

Disable /modules
Disable /icons
Disable Request Method TRACE

ServerSignature Off
ServerTokens Prod


##Php settings
session_save_path /tmp (outside of virtual host)
request_order GPC


#### browscap
Note:
In order for this to work, your browscap configuration setting in php.ini must point to the correct location of the browscap.ini file on your system.

browscap.ini is not bundled with PHP, but you may find an up-to-date » php_browscap.ini file here.

While browscap.ini contains information on many browsers, it relies on user updates to keep the database current. The format of the file is fairly self-explanatory.

##Virtual Host
The name of virual host is the name of the domain.
>The name for this example is dev.example.com

openbasedir must be enabled
ssl support must be enabled
>check that certificate is valid and not expired

- .../dev.example.com

    | Perm  | Name            | Mod |
    |-------|-----------------|:---:|
    | Owner | dev.example.com |  7  |
    | Group | dev.example.com |  7  |
    | Other |                 |  0  |

  - ./public
    
    Apache when create a dir or file, it will set group to dev.example.com.

    | Perm  | Name            | Mod |
    |-------|-----------------|:---:|
    | Owner | apache          |  7  |
    | Group | dev.example.com |  7  |
    | Other |                 |  0  |

     - Hcore Folders

  - ./log
     
     All log relative of this virtual host: (error_log, access_log, ecc)
     The log must be Rotate and backup will store out of this virtual host   
        
    | Perm  | Name            | Mod |
    |-------|-----------------|:---:|
    | Owner | log             |  7  |
    | Group | dev.example.com |  7  |
    | Other |                 |  0  |

  - ./cronjobs
  
    | Perm  | Name            | Mod |
    |-------|-----------------|:---:|
    | Owner | php             |  7  |
    | Group | dev.example.com |  7  |
    | Other |                 |  0  |

  - ./home
    
    Folder where each user trust start to browsing the virtual host via ssh.
    
    | Perm  | Name            | Mod |
    |-------|-----------------|:---:|
    | Owner | dev.example.com |  7  |
    | Group | dev.example.com |  5  |
    | Other |                 |  0  |




#Hcore

- Dir Stuct
  - ./cache
    - ./logs
    - ./.thumbs
  - ./app
    - ./api
    - ./controllers
    - ./views
    
  - ./uploads