# Inflect Command

Display what the inflector would do internally.
```
bin/cake inflect {word} {action(s)}
```

"all" will display all actions and their results:
```
bin/cake inflect FooBar all
```

You can also just run specific actions or explore a chain of actions:

```
bin/cake inflect "Foo Bar" pluralize,underscore
```
should output something like
```
Foo Bar
Chained:
- Pluralized form            : Foo Bars
- under_scored_form          : foo bars
```
