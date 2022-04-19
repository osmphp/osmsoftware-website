# Logging

After a system update, I've got some problem with my laptop, and one thing to check was system log files.

This got me thinking: what makes a good log for my own application? And I think that application logs are as useful as they are able to answer potential questions I may have when an application is in production.

Let's think what kind of questions I might have, and what information might be useful to answer them:

{{ toc }}

**Later**. The following is written as if it's already implemented. Unfortunately, it's not.

### meta.abstract

After a system update, I've got some problem with my laptop, and one thing to check was system log files.

This got me thinking: what makes a good log for my own application? And I think that application logs are as useful as they are able to answer potential questions I may have when an application is in production.

Let's think what kind of questions I might have, and what information might be useful to answer them.
 
## Why Doesn't It Work? 

While using the application, you may encounter an error in your application that occurred either on a server or in a browser.  

In this case you know the exact steps you made to get the error, the exact data you entered, and the full stack trace of the error. So you can reproduce the issue in your development environment, debug it, and, finally, fix it. 

It's useful to log stack traces of all errors that happen on the server or in browsers. In addition, it's useful to collect the steps that led to the error, and entered data.   

All stack traces flows into a single file, the `error.log` file. In a browser, some code could catch all errors and send them to the server. The absence of this file indicates that no one experiences obvious errors.

User actions and data flow into `action.log` file. The larger the user base, the more disk space this log may consume, so use aggressive truncation strategy. 

Both logs register time and context (user ID, session ID, client/server and other). Use `osm search:log '<context>'` to collect entries from all logs.

**Notice**. In terms of GDPR, you need to get user's consent for collecting some of this data, as it's not essential (though helpful) for providing the service.

## Why Is It Slow?

Another kind of logging is needed when you see something strange, but you can't explain or reproduce it. 

In case of my laptop, I noticed some lags while scrolling or typing. Unfortunately, I didn't find any log entry that could give me a clue why it happens.

All application performance failures (for example, if a route or a command doesn't execute within a specified period of time) flow into the `slow.log` file. In addition to context information, it also contains full application performance profile (if enabled), information about consumed and available system resources, as well as application load. 

In the application configuration, you may specify what is considered "bad" performance, and various rules for not logging false positives.  

## Why Is Data Incorrect?

Asynchronously computed properties and other non-trivial logic may result in an application that doesn't "auto-magically" shows you the data that you expect.

You may ask for example: "Why this stock quantity is 15? I think it should be 10."

And the only way to answer that is to see the log of all computations of a given value.

Logging all application computations may take enormous amounts of disk space. Fortunately, these questions are rare, you may normally enable computation logging only for certain properties, and only while you are not sure that they contain correct values.

All computations flow into the `indexing.log` file. For every computation, initial value, applied formula, and the final value are logged.

## Why Is It Resource-Hungry?

Some applications eat significant amount of memory and CPU. If left unchecked, unjustified resource consumption may lead to failing servers, or hosting costs going through the roof.

**Later**. I assume that there are tools such as NewRelic that help with that, and the application needs certain integration with such tools. Eventually, I'll try them out and see how they fit.

Alternatively, the application may log its resource consumption, and provide means to check it.

## How Am I Doing?

Metrics are important. You may collect and display metrics from the database, or from logs. For example, the `action.log` file mentioned above is a good candidate for collecting metrics from it.

