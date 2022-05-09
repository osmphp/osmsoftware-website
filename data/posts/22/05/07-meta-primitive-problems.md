# Primitive Problems

Estimating projects is hard. Below is my approach for getting better at it.

It's based on a concept of a "primitive problem" - something non-trivial that you can solve in one go.

You can use primitive problems as story points in agile project management. 

Contents:

{{ toc }}

### meta.abstract

Estimating projects is hard. Here is my approach for getting better at it.

It's based on a concept of a "primitive problem" - something non-trivial that you can solve in one go.

You can use primitive problems as story points in agile project management.

## Creating Vs Improving Products

Software products are never "done". However, after having "done" enough, they start to be useful, and getting better and better. 

Let's say that before it starts being useful, the product is being *created*, and after it's created, it's being *improved*.

Creating a product and improving a product is essentially the same activity, with the only difference that improvements bring value and user feedback fast while creation involves putting in a lot of effort without any user feedback. 

Splitting product lifetime into creation and improvement phases help to avoid the feeling of infinite product scope. Creating a minimum product and each further improvement has a finite scope.

## Solving Problems Vs Spending Hours

How it's the same then? 

Both when creating or improving a product, you solve *primitive problems*. It doesn't mean that the primitive problems are easy, it means that they are of manageable size. For me, a primitive problem is the one that I can resolve in one sit, or a maximum in two sits. 

Even if you tackle a complex problem, it solved by splitting it into primitive problems and then solving them one by one.

**Notice**. Primitive problems are not trivial ones. A trivial problem is something that takes 5 minutes or less - be it a telephone call or a quick fix. Just do it right now, and don't count it as an achievement.

**Notice**. Primitive problems are not tasks, either. For example, a type conversion task envelopes several primitive problems: defining "done", writing a failing test, and solving every problem that you encounter until the test succeeds.

Why primitive problems are important? I believe that primitive problems (`pp`) are a better unit of measure for work than hours (`h`).
 
Thinking in terms of `pp` helps to internally prioritize solving more problems instead of working more.

## Estimates

Each finite scope has a finite number of `pp`, Once you solve all of them, the scope is complete.  

`pp`s are similar in size. If, let's say, your project takes `100pp` to solve, and you know that, on average, you solve `20pp` a week, the project's ETA is 5 weeks.  

However, estimating projects - dividing them into `pp` - is not exact science. Splitting the initial product scope into tasks is hard, and even if you do so, you don't know exactly how many `pp` you'll encounter in each task.

The only valid source of knowledge for project estimates is past history. Document each task you worked on (H2 in my blog), and each `pp` that you solved (H3 in my blog). 

After a project is finished, count the total number of `pp` that you solved. It's a good estimate for a similar project.

## Velocity

Also, knowing the actual project duration, calculate your *velocity* - how many `pp` you solve in week.

Even if you suck at estimating projects like I do, just striving for better velocity will make your projects better.

**Notice**. Don't worry if you feel that some `pp` are harder or easier than the others. Indeed, it's not correct to compare days or even weeks by mere number of `pp` solved. However, the larger your past history "database" becomes, the more individual `pp` differences even out. 

## Iterations

Only after collecting enough past history, you can get better at estimating projects. Then, you can effectively split creating a product into iterations (or "sprints" in Agile) - fixed time periods, packed with a fixed number of `pp`.

Or, when improving a product you can pack many improvements into an iteration, again, based on your velocity measured in `pp`.   

