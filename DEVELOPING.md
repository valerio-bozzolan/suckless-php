# Boz PHP - Another PHP Framework
## Project structure
* `load.php` - Should be unversioned (database credentials, `ROOT`, `ABSPATH` constants, etc.)
* `load-post.php` - Can be versioned (roles, permissions, JavaScript and CSS files, etc.)

## Sample page
Here an example of `index.php`:

```php
<?php require 'load.php' ?>

...

<div class="container">
    <p><?= __( "Welcome" ) ?></p>
</div>

...
```

Long story short: just put a `require 'load.php'` in top of your template to access the framework.

In the next examples the `require 'load.php'` part is implicit.

## JSON

An example of a JSON API page could be this:

```php
// do some pre-conditions
if( !isset( $_GET['ping'] ) ) {

	// die with an HTTP status code 400 (Bad Request) and show a message
	json_error( 400, 'missing-ping', "Please specify the 'ping' GET argument" );
}

json( [
	'success' => true,
	'pong'    => $_GET['ping'],
] );
```

Note that to optimize the data transfer, `false` and `null` values and empty objects will be automagically removed from the JSON.

## Query

Note that the database connection is established automagically only if you need it. Now some examples.

### Query (declarative)

```php
query( "ALTER TABLE ADD COLUMN `hello` ..." );
```

### Select rows (full list)

```php
// results as a pure array
$posts = query_results( "SELECT post_title FROM wp_post" );

// as example, let's expose them in JSON
json( $posts );
```

Note that `query_results()` is better over `query_generator()` if:
* You need the full list (e.g. to count them, etc.)
* You want to read the list multiple times

### Select rows (generator)

```php
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

```php
// get a pure array
$posts = ( new Query() )
    ->from( 'post' )
    ->queryResults();
```

```php
// generator of results
$posts = ( new Query() )
    ->from( 'post' )
    ->queryGenerator();
```

```php
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

```php
insert_row( 'post', [
    new DBCol( 'post_ID',                $id, 'd' ),
    new DBCol( 'post_title',          $title, 's' ),
    new DBCol( 'post_creation_date', 'NOW()', '-' ),
] );
```

Object-oriented way:

```php
( new Query() )
    ->from( 'post' )
    ->insertRow( [
        new DBCol( 'post_ID',                $id, 'd' ),
        new DBCol( 'post_title',          $title, 's' ),
        new DBCol( 'post_creation_date', 'NOW()', '-' ),
    ] );
```

## Delete

```php
( new Query() )
    ->from( 'post' )
    ->whereStr( 'post_author', 'jhon' )
    ->whereInt( 'post_stub,     1     )
    ->delete();
```

## Database row → object (mapping)

```php
/**
 * Declaration of my custom class
 */
class Post extends Queried {

	/**
	 * Table name without table prefix
	 */
	const T = 'post';

	/**
	 * An example method
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->post_title;
	}
}


// retrieve a Post object or null
$post = ( new Post() )
	->whereInt( 'post_ID', 1 )
	->queryRow();

if( $post ) {
	// do stuff

	$title = $post->getTitle();
}
```

## CSRF protection

To both identify a form and secure it against [Cross-site request forgery](https://en.wikipedia.org/wiki/Cross-site_request_forgery) you can use `form_action()` and `is_action()`:

```php
<?php
	if( is_action( 'save-user' ) ) {
		// do stuff!
	}
?>

<form method="post">
	<?php form_action( 'save-user' ) ?>	
	<button type="submit">Save</button>
</form>
```
