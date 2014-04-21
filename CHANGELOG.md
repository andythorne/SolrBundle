#Rebuild Changelog

There are several major changes with the structure and interaction with the rebuild fork. Here are the major ones, and
why it was changed:

##Query Creation, Repositories, Mappers and Doctrine
My original need to modify the bundle came from it's interaction with Doctrine. In my system, Solr is queried and ids
are returned. The database is then hit to extract full entities. A lot of this is done for searching, so entity objects
are not needed. I needed a way to hydrate as an array. This in-turn needed the query to be provided. I ended uo using the 
query builder to provide greater control when filtering within the bundle.

I then found out that, for each solr representation of an entity that was returned, the database was hit, resulting in 
potentially hundreds of queries being run.

I think [florian](https://github.com/floriansemm/SolrBundle) had the right idea in providing a solr Repository, so I
extended it to provide the needed functionality:

###MetaInformation
I never really understood why the MetaInformation class contained actual entity objects. Maybe it's just a bad name for
the class, but it seemed wrong, so they have now been decoupled. It seemed to be creating new entities and reflection
classes all over the place. The mapping method `extractSolrValues($entity)` has been added to convert an entity into
solr-ready document values.

The MetaInformation has been integrated into Repository class. Methods that were being provided by the Solr class, have
been de-coupled and moved into the Repository class. Any methods still used in Solr now require the meta information as
well. That may sound bad, but as all manipulation happens via the Repository class, the meta is automatically passed
through.

* `Solr->createQuery` - This method still exists, but now only returns the response from solr.
* `Solr->XXXDocument` - Direct Entity/Document manipulation has been moved into the Repository.

###Mappers and Dehydrators
The main change here is that instead of a hydration being called for every solr entity representation, it is now called
on the array of entity representations (i.e. the response from solr). This allows doctrine to query for a set of ids, as 
described next.

###Doctrine
Doctrine is now queried with the QueryBuilder by all methods. This allows us to join on any EntityFields or
CollectionFields (See Annotations section below) and specify the doctrine hydrate mode.


##Annotations
I think there were a few fairly big annotations missing to query on, which were 
[awaiting implementation](https://github.com/floriansemm/SolrBundle/issues/2) (unfortunately that changes made
elsewhere in this fork render the two implementations incompatible):

* Properties within Entity relationships
* Properties within Collections of Entities
* Fields that are not mapped in the entity, but are still needed. For example combination fields.
* Ability to map a property to another name. For example `$tags` should map to `tag` within solr.

So, these changes have been made:


###Modified Annotation: Field
There is a new "name" property. This allows you to map the entity property to a different name in solr.

```php
    /**
     * @var string
     * @Solr\Field(name="article_title")
     */
    protected $title;
```

###New Annotation: EntityField
This will map properties from within an entity into solr.

This will map $category->name into solr. You can specify multiple properties, however they will all be mapped to the
`solr_category` query string.

```php

    /**
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="BlogCategory")
     *
     * @Solr\EntityField(name="solr_category", properties={"name"})
     */
    protected $category;

```

Property   | Type   | Required
-----------|--------|---------
name       | string | optional
properties | array  | required

###New Annotation: CollectionField
This will map properties from entities within a collection into solr. Options are the same as `EntityField`.

```php

    /**
     * @var Tag[]
     *
     * @ORM\ManyToMany(targetEntity="BlogTag")
     *
     * @Solr\CollectionField(name="slor_tag", properties={"name", "description"})
     */
    protected $tags;

```

Property   | Type   | Required
-----------|--------|---------
name       | string | optional
properties | array  | required

###New Annotation: MetaFields
This will map any fields that are in solr, but do not have a property. It is defined at the class level.

```php

    /**
     * @Solr\Document
     * @Solr\MetaFields(fields={{"name"="text"}})
     *
     * @ORM\Entity
     */
    class Article
    {
        ...
    }
```

Property   | Type   | Required | Notes
-----------|--------|----------|---------------------------------------------------------------------------------------
fields     | array  | required | The array is a key/value pair of options you would usually pass to a Field annotation.

