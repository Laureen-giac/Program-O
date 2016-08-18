<?php

class MagicNumbers {
    public static $node_activation_cnt = 4;  // minimum number of activations to suggest atomic pattern
    public static $node_size = 4;  // minimum number of branches to suggest wildcard pattern
    public static $displayed_input_sample_size = 6;
    public static $max_history = 32;
    public static $repetition_count = 2;
    public static $max_stars = 1000;
    public static $max_graph_height = 100000;
    public static $max_substitutions = 10000;
    public static $max_recursion_depth = 765; // assuming java -Xmx512M
    public static $max_recursion_count = 2048;
    public static $max_trace_length = 2048;
    public static $max_loops = 10000;
    public static $estimated_brain_size = 5000;
    public static $max_natural_number_digits = 10000;
    public static $brain_print_size = 100; // largest size of brain to print to System.out
}