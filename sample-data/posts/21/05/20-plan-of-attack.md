# Plan of attack

As [mentioned before](18-welcome.md), this very website is based on the `osmphp/framework` package. This blog post is the second in the series describing how it was built. This post covers the "plan of attack" - where to start, what to do next, and what to finish with.

{{ toc }}

## Create the project, and render the home page

Make a minimum that counts as a website - a project with a styled home page.

## Prepare post data

Given a sample data set covering all the edge cases, unit test:

1. Make sure that blog post links and images work when browsing the source code on GitHub.
   
2. Parsing a Markdown file. Given a filename, check that all the data (including metadata) is extracted from the Markdown file correctly. Throw an exception if there is no such file.

3. Indexing a DB record(s). Given a filename/directory, INSERT/UPDATE matching DB record(s). Given a DB record, check for matching file, and mark the record as deleted if the file doesn't exist anymore. Prune records deleted before X days.

4. Indexing an ElasticSearch document(s). Given a record ID(s), INSERT/UPDATE/DELETE an ElasticSearch document(s).

5. Keyword search and filtering by tag/year/month. ElasticSearch should return the first page of correct post IDs, DB should return records containing filenames, and the file system should return parsed Markdown files. Handle missing records and files - when indexing is behind. Handle subsequent paging requests. Handle ElasticSearch document count changes between paging requests.

6. Broken link reporting.

## Render posts

Given the same sample data set, unit test and style (where appropriate): 

1. Blog post, month page, year page, all posts page, tag page routing (that is, URL recognition) and URL generation.
2. Blog post page. 
3. Redirects.
4. Tag, month, year, all posts pages.
5. Search page.
6. Breadcrumbs.
7. Tags.
8. Infinite scrolling.
9. Tag substitution.
10. Variable assignment.

## Publish the project

1. Test `osmcommerce.local` under Nginx.
2. Upload, configure and test `osmcommerce.com` on a remote server.
   
## Handle push notifications

Create and unit test a helper project for applying updates on a server after receiving push notifications from GitHub.

1. Create a new `push.osmcommerce.com` project on a remote server.
2. Create a route that handles GitHub notifications (aka Webhook). Temporarily, log the requests into a file, and throw `NotImplemented` exception.
3. Locally, unit test that GitHub notifications are processed according to configuration in `data/github-push/{{ repo_name }}.json` that specifies what branch or version tag to accept, and what script to run.
4. Leave the GitHub notification validation in the route, and move actual script execution to a queued job.  
5. Report errors to an email specified in configuration.
6. Deploy, and test with actual GitHub notifications.
7. Extract the reusable code into `osmphp/push` package, and create a `osmphp/push-project` project template.

## Implement users and comments 

For comments to work, typical `/login`, `/register`, `/logout`, `/confirm-email`, `/forgot-password`, `/reset-password` and `/account` routes and pages have to be implemented. 

Unit test and style (where appropriate):

1. `/register` page.
2. `/register` -> email -> `/confirm-email` workflow.
3. `/login` page (including administrator login specified in configuration).
4. `/forgot-password` -> email -> `/reset-password` workflow.
5. `/account` page.
6. Commenting on a blog post page.
7. Deleting comments, and blocking users for administrator.
8. Notifying users, including administrator about new comments.