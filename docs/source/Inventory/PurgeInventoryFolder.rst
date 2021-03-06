PurgeInventoryFolder Method
===========================

Removes all child inventory nodes in a folder.


Request Format
--------------

+-----------------+-----------------------------+---------+-------------+
| *Parameter*     | *Description*               | *Type*  | *Required*  |
+=================+=============================+=========+=============+
| `RequestMethod` | PurgeInventoryFolder        | String  | Yes         | 
+-----------------+-----------------------------+---------+-------------+
| `OwnerID`       | UUID of the inventory owner | UUID    | Yes         | 
+-----------------+-----------------------------+---------+-------------+
| `FolderID`      | UUID of the folder to purge | UUID    | Yes         | 
+-----------------+-----------------------------+---------+-------------+

Sample request: ::

    RequestMethod=PurgeInventoryFolder
    &OwnerID=a187cee3-a21d-4ff1-8a48-30df0c15ce00
    &ItemID=ceb3c0c7-c610-49cb-b89f-479b67f1fcbe


Response Format
---------------

+-------------+-------------------------------------------------+---------+
| *Parameter* | *Description*                                   | *Type*  |
+=============+=================================================+=========+
| `Success`   | True if the folder was purged or did not exist, | Boolean |
|             | False if a Message was returned                 |         |
+-------------+-------------------------------------------------+---------+
| `Message`   | Error message                                   | String  |
+-------------+-------------------------------------------------+---------+

Success: ::

    {
        "Success":true,
    }


Failure: ::

    {
        "Success":false,
        "Message":"An unknown error occurred"
    }

