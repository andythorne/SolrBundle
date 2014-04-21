[![Build Status](https://api.travis-ci.org/andythorne/SolrBundle.png?branch=rebuild)](https://travis-ci.org/andythorne/SolrBundle)

This Bundle provides a simple API to index and query a Solr Index.

# Configuration

The bundle requires a working doctrine-orm or doctrine-mongodb configuration. There are no differences in the use.

## Install the Bundle

Bundle

1.  Register bundle in AppKernel.php

        # app/AppKernel.php

        $bundles = array(
            // ...
            new FS\SolrBundle\FSSolrBundle(),
            // ...
        );

2.  Add Bundle to autoload

    A. Via composer, add in your composer.json

        "require": {
            // ...
            "andythorne/solr-bundle": "dev-master"
        }

    B.  or manually, in app/autoload.php

    i. In symfony 2.1.4 (supposing you clone the bundle in vendor/andythorne/solr-bundle/FS/, making available vendor/andythorne/solr-bundle/FS/SolrBundle/FSSolrBundle.php)

        $loader->add('FS\\SolrBundle', array(__DIR__.'/../vendor/andythorne/solr-bundle'));

    ii. in older version it could be

        $loader->registerNamespaces(array(
            // ...
            'FS' => __DIR__.'/../vendor/bundles',
            // ...
        ));

## Multiple Indexes

You have to setup the connection options

    # app/config/config.yml
    fs_solr:
      endpoints:
        default:
          host: host
          port: 8983
          path: /solr/
          core: corename
          timeout: 5
      clients:
        default:
          endpoints: [default]

With this config you have access to the service `solr.client.default`. If you have mo/nre client you can access them with the call `solr.client.clientname`

# Usage #

## Annotations

To put an entity to the index, you must add some annotations to your entity:

```php
    // your Entity

    // ....
    use FS\SolrBundle\Doctrine\Annotation as Solr;

    /**
     * @Solr\Document(repository="Full\Qualified\Class\Name")
     * @Solr\MetaFields(fields={{"name"="text"}})
     * @ORM\Table()
     */
    class Post
    {
        /**
         * @Solr\Id
         *
         * @ORM\Column(name="id", type="integer")
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="AUTO")
         */

        private $id;
        /**
         *
         * @Solr\Field(type="string")
         *
         * @ORM\Column(name="title", type="string", length=255)
         */
        private $title = '';

        /**
        *
        * @Solr\Field(type="string")
        *
        * @ORM\Column(name="text", type="text")
        */
        private $text = '';

        /**
         * @var Category
         *
         * @ORM\ManyToOne(targetEntity="BlogCategory")
         *
         * @Solr\EntityField(properties={"name"})
         */
        protected $category;

        /**
         * @var BlogTag[]
         *
         * @ORM\ManyToMany(targetEntity="BlogTag", inversedBy="posts", cascade={"persist"})
         * @ORM\JoinTable(name="blog_post_tag")
         *
         * @Solr\CollectionField(name="tag", properties={"name"})
         */
        protected $tags;

        /**
        * @Solr\Field(type="date")
        *
        * @ORM\Column(name="created_at", type="datetime")
        */
        private $created_at = null;
    }
```

### Supported field types

Currently is a basic set of types implemented.

- string
- text
- date
- integer
- float
- double
- long
- boolean

It is possible to use custum field types (schema.xml).

### Filter annotation

In some cases an entity should not be indexed. For this you have the `SynchronizationFilter` Annotation.


```php
    /**
    * @Solr\Document
    * @Solr\SynchronizationFilter(callback="shouldBeIndexed")
    */
    class SomeEntity
    {
        /**
        * @return boolean
        */
        public function shouldBeIndexed()
        {
            // put your logic here
        }
    }
```

The callback property specifies an callable function, which decides whether the should index or not.


## Solr field configuration

Solr comes with a set of predefined field-name/field-types mapping:

- title (solr-type: general_text)
- text (solr-type: general_text)
- category (solr-type: general_text)
- content_type (solr-type: string)

For details have a look into your schema.xml.

So if you have an entity with a property "category", then you don't need a type-declaration in the annotation:

```php
    /**
    * @Solr\Field
    * @ORM\Column(name="title", type="text")
    */
    private $title = '';
```

The field has in this case automaticaly the type "string".

If you persist this entity, it will put automaticlly to the index. Update and delete happens automatically too.

## Query a field of a document

To query the index you have to call the base solr service and call getRepository('entity:class'), just like you would
for doctrine:

```php
    $query = $this->get('solr')->getRepository('AcmeDemoBundle:Post');
```

You then have 5 methods available:

* `find($id)` - Find one entity by identifier
* `findBy($criteria)` - Find all by $criteria (array) (note this can be restricted by Solr's default page size of 10)
* `findOneBy($criteria)` - Find one by $criteria (array)
* `findAll()` - Find all entities (note this can be restricted by Solr's default page size of 10)
* `findAllBy($criteria)` - Find all entities by querying all fields on $criteria (string)
* `createFindBy($criteria, $fields = null, $limit = 10, $queryBuilder = null, $hydration = Query::HYDRATE_OBJECT)` - More dynamic version of above methods. See below:

`createFindBy(...)` allows you to modify what you recieve back from solr, after it has been passed through the doctrine
mapper. So, for example, if you have an `Article` entity, which has a collection of `Tag`  Entities and a `Category`
Entity, you can fetch all in one command by providing a query builder:

```php
    $solrRepo = $this->solr->getRepository('AcmeDemoBundle:Article');

    $qb = $repo->createQueryBuilder('p');
    $qb->select('p,c,ta');
    $qb->join('p.category', 'c');
    $qb->join('p.tags', 'ta');

    $result = $solrRepo->createFindBy(array('text' => $query), array('id'), 10, $qb, Query::HYDRATE_ARRAY);
```

The $result array contains all found entities. The solr-service does all mappings from SolrDocument to your entity for
you.

## Configure HydrationModes

HydrationMode tells the Bundle how to create an entity from a document.

1. `FS\SolrBundle\Doctrine\Hydration\HydrationModes::HYDRATE_INDEX` - use only the data from solr
2. `FS\SolrBundle\Doctrine\Hydration\HydrationModes::HYDRATE_DOCTRINE` - merge the data from solr with the entire doctrine-entity

Set with the repository:

```php
    $repo = $this->get('solr')->getRepository('AcmeDemoBundle:Post');
    $repo->setHydrationMode($mode)
```

With a custom document-repository you have to set the property `$hydrationMode` itself:

```php
    public function find($id)
    {
        $this->hydrationMode = HydrationModes::HYDRATE_INDEX;

        return parent::find($id);
    }
```

## Index an entity

To index your entities manually, you can do it the following way:

```php
    $solrRepo = $this->solr->getRepository('AcmeDemoBundle:Article');

    $solrRepo->insert($entity);
    $solrRepo->update($entity);
    $solrRepo->delete($entity);
```

`delete()` requires that the entity-id is set.

## Use document repositories

If you specify your own repository you must extend the `FS\SolrBundle\Repository\Repository` class. The useage is the same
like Doctrine-Repositories:

```php
    $myRepository = $this->get('solr')->getRepository('AcmeDemoBundle:Post');
    $result = $myRepository->mySpecialFindMethod();
```

If you haven't declared a concrete repository in your entity and you calling `$this->get('solr')->getRepository('AcmeDemoBundle:Post')`, you will
get an instance of `FS\SolrBundle\Repository\Repository`.

## Commands

There are two commands with this bundle:

* `solr:index:clear` - delete all documents in the index
* `solr:synchronize` - synchronize the db with the index
