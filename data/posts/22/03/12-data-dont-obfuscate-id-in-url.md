# Don't Obfuscate ID In URL

Some people worry that providing ID in the URL is bad for security. For example:

1. If you see `/orders/?id=9` in the browser's URL bar, you can enter `/orders/?id=8` manually, and see the order that doesn't belong to you.
2. You can easily scrape the whole website by just enumerating through all `id=1`, `id=2`, and so on until you start getting 404 responses.
3. You can inject SQL into the underlying query, or execute malicious Javascript by passing "bad" things instead of valid IDs.

Instead of ID, they propose generating a random GUID, something like `f9224f30-592e-4801-88d4-6790c4ebbbdd`, store it in the database record as an unique key, and use it in the URL for addressing database records.

Frankly, I don't think so:

1. Security by obscurity is bad type of security. It means that your data is not safe if a malicious user just doesn't know its URL. Much better way of handling this is to check if the current user is authorized to access a given ID, and if not, deny them with 403 response.

   I'll implement to the object-level security later, but the general idea is that every object should have an owner, the object owner may share it with other users, and all such information is stored in the object's properties.

2. Scraping of a public website can be done by following links on its pages, and for the sake of preventing scraping hiding object ID doesn't help.

3. SQL injections are prevented by sanitizing input and using SQL query bindings. XSS injections are prevented by escaping output. Using raw object IDs doesn't make prevention of such attacks easier or harder.

To sum up, I don't really see any reason why you shouldn't use ID in the URL. Compared to GUID it's much more readable.

Oh, there is a reason. If someone sees `/orders/?id=9` in the URL, they might understand that you don't have that many orders. However, I wouldn't worry about that - make more sales, don't try to look bigger than you are, and you'll be just fine!

### meta.abstract

In my opinion, obfuscating object ID in a URL is not worth the effort, and here is why.