<?php

use Bluerhinos\phpMQTT;

require('./classes/phpMQTT.php');

$server = 'broker.emqx.io'; // MQTT Broker address
$port = 1883;             // Broker port
$username = '';               // Username
$password = '';               // Password
$clientID = 'phpMQTT-client'; // Unique Client ID

$mqtt = new phpMQTT($server, $port, $clientID);

if (!$mqtt->connect(true, NULL, $username, $password)) {
    exit('Failed to connect to the MQTT broker.');
}

$topic = 'task10.1/topic';
$qosLevels = [0, 1, 2];

function createSubscriber($id, $topic, $qos)
{
    global $server, $port, $username, $password;
    $subscriber = new phpMQTT($server, $port, "Subscriber-$id");
    if (!$subscriber->connect(true, NULL, $username, $password)) {
        exit("Subscriber $id failed to connect.\n");
    }
    $subscriber->subscribe([$topic => ['qos' => $qos, 'function' => function ($topic, $msg) use ($id) {
        echo "Subscriber-$id received: $msg on topic $topic\n";
    }]]);

    return $subscriber;
}

foreach ($qosLevels as $qos) {
    echo "Testing QoS $qos...\n";

    $sub1 = createSubscriber(1, $topic, $qos);
    $sub2 = createSubscriber(2, $topic, $qos);

    $messages = ["Message 1", "Message 2", "Message 3"];
    foreach ($messages as $msg) {
        $mqtt->publish($topic, $msg, $qos);
        echo "Published: $msg with QoS $qos\n";
        sleep(5);
    }


    $sub2->close();
    echo "Subscriber 2 disabled.\n";

    $additionalMessages = ["Message 4", "Message 5"];
    foreach ($additionalMessages as $msg) {
        $mqtt->publish($topic, $msg, $qos);
        echo "Published: $msg with QoS $qos\n";
        sleep(1);
    }

    $sub2 = createSubscriber(2, $topic, $qos);
    echo "Subscriber 2 re-enabled.\n";

    $finalMessages = ["Message 6", "Message 7"];
    foreach ($finalMessages as $msg) {
        $mqtt->publish($topic, $msg, $qos);
        echo "Published: $msg with QoS $qos\n";
        sleep(1);
    }

    $sub1->close();
    $sub2->close();
}

$mqtt->close();

echo "Task 10.1 completed.\n";
?>
