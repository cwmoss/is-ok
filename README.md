##### phpunit debug

    vendor/bin/phpunit tests/ --display-warnings --display-notices --log-events-text /dev/stdout

### validations

    required
    min val
    max val
    len val/is min max
    format val (regex)
    inlist val (array, string => context.array, context.callable, data.array, data.callable
    confirmed to
    accepted
    email
    numeric
    decimal val
    integer val/is min max
