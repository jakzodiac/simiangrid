GetInventoryNode Method
=======================

Retrieve an inventory item or folder (optionally including direct descendants).


Request Format
--------------

+------------------+-----------------------------+---------+-----------------+
| *Parameter*      | *Description*               | *Type*  | *Required*      |
+==================+=============================+=========+=================+
| `RequestMethod`  | GetInventoryNode            | String  | Yes             |
+------------------+-----------------------------+---------+-----------------+
| `ItemID`         | UUID of the item or folder  | UUID    | Yes             |
+------------------+-----------------------------+---------+-----------------+
| `OwnerID`        | UUID of the item or folder  | UUID    | Yes             |
|                  | owner                       |         |                 |
+------------------+-----------------------------+---------+-----------------+
| `IncludeFolders` | If true and ItemID is a     | Boolean | Optional,       |
|                  | folder, include child items |         | defaults to     |
|                  |                             |         | TRUE            |
+------------------+-----------------------------+---------+-----------------+
| `IncludeItems`   | If true and ItemID is a     | Boolean | Optional,       |
|                  | folder, include child       |         | defaults to     |
|                  | folders                     |         | TRUE            |
+------------------+-----------------------------+---------+-----------------+
| `ChildrenOnly`   | If true, only this node and | Boolean | Optional,       |
|                  | direct descendants are      |         | defaults to     |
|                  | fetched                     |         | TRUE            |
+------------------+-----------------------------+---------+-----------------+

|

Sample request: ::

    RequestMethod=GetInventoryItem
    &ItemID=cbee00bb-1f26-414a-a0aa-9c5a7156fe64
    &OwnerID=9ffd5a95-b8bd-4d91-bbed-ded4c80ba151
    &IncludeFolders=0


Response Format
---------------

+-------------+----------------------------------------------------+---------+
| *Parameter* | *Description*                                      | *Type*  |
+=============+====================================================+=========+
| `Success`   | True if an array of items and folders was          | Boolean |
|             | returned, False if a Message was returned          |         |
+-------------+----------------------------------------------------+---------+
| `Items`     | The retrieved items and folders. If a folder and   | Array,  |
|             | its children were requested, the first record will | see     |
|             | be the requested folder                            | below   |
+-------------+----------------------------------------------------+---------+
| `Message`   | Error message                                      | String  |
+-------------+----------------------------------------------------+---------+

Returns an array containing either items, folders, or both depending
on the parameters used in the request. Each returned record can be
either an item or a folder. If a folder and its children were
requested, the first record will be the requested folder.



Inventory Folder Format
-----------------------

+----------------+---------------------------------------------+-------------+
| *Parameter*    |  *Description*                              | *Type*      |
+================+=============================================+=============+
| `Type`         | Inventory node type, will always be         | String      |
|                | "Folder"                                    |             |
+----------------+---------------------------------------------+-------------+
| `ID`           | UUID of the folder                          | UUID        | 
+----------------+---------------------------------------------+-------------+
| `ParentID`     | UUID of the parent folder, or UUID.Zero if  | UUID        |
|                | this is the root inventory folder           |             |
+----------------+---------------------------------------------+-------------+
| `OwnerID`      | UUID of the folder owner                    | UUID        |
+----------------+---------------------------------------------+-------------+
| `Name`         | Folder name                                 | String      |
+----------------+---------------------------------------------+-------------+
| `CreationDate` | UTC timestamp this folder was created       | Integer     |
+----------------+---------------------------------------------+-------------+
| `ContentType`  | Preferred content type for the folder.      | ContentType |
|                | Default is application/octet-stream         |             |
+----------------+---------------------------------------------+-------------+
| `ExtraData`    | Free-form JSON data associated with this    | JSON        |
|                | folder                                      |             |
+----------------+---------------------------------------------+-------------+
| `Version`      | Version number of the folder, incremented   | Integer     |
|                | each time children are added or removed     |             |
+----------------+---------------------------------------------+-------------+


Inventory Item Format
---------------------

+----------------+---------------------------------------------+-------------+
| *Parameter*    | *Description*                               | *Type*      | 
+----------------+---------------------------------------------+-------------+
| `Type`         | Inventory node type, will always be "Item"  | String      | 
+----------------+---------------------------------------------+-------------+
| `ID`           | UUID of the item                            | UUID        | 
+----------------+---------------------------------------------+-------------+
| `ParentID`     | UUID of the parent folder                   | UUID        | 
+----------------+---------------------------------------------+-------------+
| `OwnerID`      | UUID of the item owner                      | UUID        | 
+----------------+---------------------------------------------+-------------+
| `Name`         | Item name                                   | String      | 
+----------------+---------------------------------------------+-------------+
| `CreationDate` | UTC timestamp this item was created         | Integer     | 
+----------------+---------------------------------------------+-------------+
| `ContentType`  | Content type of the asset this item points  | ContentType |
|                | to                                          |             |
+----------------+---------------------------------------------+-------------+
| `ExtraData`    | Free-form JSON data associated with this    | JSON        |
|                | item                                        |             | 
+----------------+---------------------------------------------+-------------+
| `AssetID`      | UUID of the asset this item points to       | UUID        | 
+----------------+---------------------------------------------+-------------+
| `Description`  | Item description                            | String      | 
+----------------+---------------------------------------------+-------------+

Success: ::

    {
        "Success":true,
        "Items":
        [
            {
                "Version":12,
                "ChildCount":12,
                "ID":"cbee00bb-1f26-414a-a0aa-9c5a7156fe64",
                "ParentID":"00000000-0000-0000-0000-000000000000",
                "OwnerID":"9ffd5a95-b8bd-4d91-bbed-ded4c80ba151",
                "Name":"Library",
                "ContentType":"application/vnd.ll.folder",
                "ExtraData":"",
                "CreationDate":1261042614,
                "Type":"Folder"
            },{
                "Version":0,
                "ChildCount":0,
                "ID":"a2bd28c4-9243-418f-887d-4aa219b0046e",
                "ParentID":"cbee00bb-1f26-414a-a0aa-9c5a7156fe64",
                "OwnerID":"9ffd5a95-b8bd-4d91-bbed-ded4c80ba151",
                "Name":"Animations",
                "ContentType":"application/vnd.ll.animation",
                "ExtraData":"",
                "CreationDate":1261042614,
                "Type":"Folder"
            }
        ]
    }


Failure: ::


    {
        "Success":false,
        "Message":"Item or folder not found"
    }

