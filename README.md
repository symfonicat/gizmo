`gizmo` is a small PHP + Twig experiment that precomputes tiny C programs before they are needed.

In basic terms:

- you pass in some words on the command line
- `gizmo.php` keeps guessing possible `0` / `1` values for those words
- it generates and compiles a C program for each guess
- it runs that program once and saves the result
- if the same guess comes up again later, it can reuse the saved result immediately

The point is to do expensive work early so later requests are fast.

### what it does

`gizmo.php` runs forever in a loop.

On each pass it:

1. Looks at the words you passed in on the command line.
2. Guesses a random `0` or `1` for each word.
3. Turns that guess into a key like `1_0` or `0_1_1`.
4. Renders a C file and a `Makefile` from Twig templates.
5. Compiles the C file if that permutation has not been built yet.
6. Runs the compiled binary if that permutation has not produced a result yet.
7. Prints the saved result.

### command line arguments

Run it like this:

```bash
php gizmo.php taco pizza
```

In that command:

- `php` starts PHP
- `gizmo.php` is the script
- `taco pizza` are the words `gizmo` uses

Those words become labels in the generated output. A line might look like:

```text
1 taco, 0 pizza
```

### permutations

one word would mean one guessed parameter:

```bash
php gizmo.php taco
```

- possible permutations: `0` and `1`
- total permutations: `2`
---

two words means two guessed parameters:

```bash
php gizmo.php taco pizza
```

- possible permutations: `0_0`, `0_1`, `1_0`, `1_1`
- total permutations: `4`
---

three words means three guessed parameters:

```bash
php gizmo.php taco pizza cat
```
- possible permutations: `0_0_0`, `0_0_1`, `0_1_0`, `0_1_1`, `1_0_0`, `1_0_1`, `1_1_0`, `1_1_1`
- total permutations: `8`
---

### why `$i` is random `0` or `1`

`$i` is the guessed parameter list for the current loop.

It uses `random_int(0, 1)` because this project is pretending that each future function parameter might be either `0` or `1`.

So instead of waiting for a real request later, it guesses a possible future parameter combination right now and computes it ahead of time.

That way, if the program needs that same combination later, the result is already there.

This is the basic precompute idea:

- guess a possible input now
- do the work now
- reuse it later

### why it stalls at first

The generated C program contains:

```c
sleep(2);
```

That `sleep(2)` is simulated work.

It is there to act like a slow calculation. The program is pretending it has to do real work to produce each result.

So when `gizmo` hits a permutation it has never seen before, output will pause for a moment. That pause is the program doing the work needed to compute that missing permutation.

### why it gets blazing fast later

After the program has been running long enough, more and more permutations have already been built and executed.

Once a permutation already exists:

- the C source is already generated
- the binary is already compiled
- the result file is already written

At that point, `gizmo` can skip the expensive part and just read the cached result from `./results/`.

That is why the beginning feels slow, but later it becomes very fast: the program has already done the work for most or all permutations for that current number of CLI arguments.

### templates

`templates/base.c.twig` contains the shared C program structure.

`templates/c.twig` fills in the generated variables and the output format string, including the `sleep(2)` simulated work.

`templates/Makefile.twig` builds the generated C source into a binary.

### important files

- `gizmo.php`: the main loop
- `templates/base.c.twig`: shared C template
- `templates/c.twig`: per-permutation C template
- `templates/Makefile.twig`: build template
- `src/`: generated C source
- `build/`: compiled binaries
- `results/`: cached output files
- `reset`: clears generated files

### reset

```bash
./reset
```

This deletes everything in `build/`, `src/`, and `results/` so the program has to warm up from scratch again.

### run

```bash
composer install
php gizmo.php taco pizza
```

At first it will pause while it computes permutations that do not exist yet. After it has seen enough of them, it becomes much faster because it is reusing cached results instead of recomputing them.
