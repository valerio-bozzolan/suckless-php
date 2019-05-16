# Boz PHP - Another PHP Framework
## Project structure
* `load.php` - Should be unversioned (database credentials, `ROOT`, `ABSPATH` constants, etc.)
* `load-post.php` - Can be versioned (roles, permissions, JavaScript and CSS files, etc.)

## Sample page
Here an example of `index.php`:

```
<?php
require 'load.php';

// your own function that print the site header
Header::spawn();
?>

<div class="container">
    <p><?php _e( "Welcome") ?></p>
</div>

<?php
// your own function that print the site footer
Footer::spawn();
?>
```

Long story short: just put a `require 'load.php'` in top of your template to access the framework.

## Query
Note that as default no database 

### Query (declarative)

```
    query( "ALTER TABLE ADD COLUMN `hello` ..." );
```

### Select rows (full list)

```
// results as a pure array
$posts = query_results( "SELECT post_title FROM wp_post" );
if( $posts ) {
    json( $posts );
}
```

Note that `query_results()` is better over `query_generator()` if:
* You need the full list (e.g. to count, to convert as JSON, etc.)
* You want to read the list multiple times

### Select rows (generator)

```
// generator of results
$posts = query_generator( "SELECT post_title FROM wp_post" );
if( $posts->valid() ) {
    foreach( $posts as $post ) {
        echo $post->post_title;
    }
}
```

Note that `query_generator()` is better over `query_results()` if:
* You do not need the full list (but just one at time)
* You do not need to read the list multiple times.

### Query builder (object-oriented)

```
// get a pure array
$posts = ( new Query() )
    ->from( 'post' )
    ->queryResults();
```

```
// generator of results
$posts = ( new Query() )
    ->from( 'post' )
    ->queryGenerator();
```

```
$posts = ( new Query() )
    ->from( 'post' )
    ->whereStr( 'post_title', $title )
    ->whereInt( 'post_author_id', $author )
    ->quereSomethingIn( 'post_status', [ 'private', 'stub', 'deleted' ] )

    // implicit join + condition
    ->from( 'comment' )
    ->where( 'comment.user_ID = user.user_ID' )

    // explicit JOIN ON
    ->joinOn( 'LEFT', 'user', 'user.user_ID', 'post.post_author_id' )
    ->queryResults();
```

## Insert

Declarative way:

```
insert_row( 'post', [
    new DBCol( 'post_ID',                $id, 'd' ),
    new DBCol( 'post_title',          $title, 's' ),
    new DBCol( 'post_creation_date', 'NOW()', '-' ),
] );
```

Object-oriented way:

```
( new Query() )
    ->from( 'post' )
    ->insertRow( [
        new DBCol( 'post_ID',                $id, 'd' ),
        new DBCol( 'post_title',          $title, 's' ),
        new DBCol( 'post_creation_date', 'NOW()', '-' ),
    ] );
```

## Database object mapping

```
// declare a class
class Post extends Queried {
    const T = 'post'; // table name

    // do something with the attributes (post_title)
    public function safePrintAmazingTitle() {
        _esc_html( "Post title: {$this->post_title}" );
    }
}

// retrieve a Post objet (or null)
$post = Post::factory()
    ->select( 'post_title' )
    ->limit( 1 )
    ->queryRow();

if( $post ) {
    $post->safePrintAmazingTitle();
}
```