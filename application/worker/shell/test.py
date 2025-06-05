import psutil
import subprocess
import time

def monitor_port_connections(port):
    connections = psutil.net_connections(kind='inet')
    tcp_connections = [conn for conn in connections if conn.status == 'ESTABLISHED' and conn.laddr.port == port]
    return len(tcp_connections)

def run_php_command():
    command = [
        "sudo",
        "-u", "www",
        "/www/server/php/73/bin/php",
        "/workspace/developSite/datacenter/AHeartRate.php",
        "stop",
        "/www/server/php/73/bin/php",
        "/workspace/developSite/datacenter/AHeartRate.php",
        "start"
    ]

    try:
        output = subprocess.check_output(command, stderr=subprocess.STDOUT)
        output_str = output.decode('utf-8')
        print("Command executed successfully.")
        print("Command output:", output_str)
    except subprocess.CalledProcessError as e:
        print("Error executing command:", e.output.decode('utf-8'))
    except Exception as e:
        print("An error occurred:", e)

def main():
    port_to_monitor = 2356
    max_connections = 1
    while True:
        num_connections = monitor_port_connections(port_to_monitor)
        print("Number of connections to port {}: {}".format(port_to_monitor, num_connections))

        if num_connections > max_connections:
            print("Maximum number of connections ({}) reached, executing PHP command...".format(max_connections))
            run_php_command()

        time.sleep(10)

if __name__ == "__main__":
    main()
