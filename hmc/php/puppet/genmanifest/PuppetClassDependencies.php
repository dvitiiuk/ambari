<?php

include_once "../util/Logger.php";

define ("SERVICE_STATE_RUNNING", "running");
define ("SERVICE_STATE_INSTALLED_AND_CONFIGURED", "installed_and_configured");
define ("SERVICE_STATE_STOPPED", "stopped");
define ("SERVICE_STATE_UNINSTALLED", "uninstalled");
define ("SERVICE_STATE_NO_OP", "no_op");
define ("SERVICE_STATE_NOT_APPLICABLE", "na");
define ("SERVICE_STATE_KEY", "service_state");

//This class defines the classes that must be present together
//on a single node manifest in given service states. Also defines the
//parameters for those classes. It also defines parameters for a
//class by declaring dependency on itself.
//However, the actual order of the classes on a single host is NOT
//determined by this class. Therefore one can define both forward and backward
//dependencies.
class PuppetClassDependencies {

  private $hostLevelDependencies;

  function __construct() {
    $this->hostLevelDependencies = array();

    //HDFS
    $this->addDependency("hdp-hadoop::namenode", SERVICE_STATE_RUNNING, "hdp-hadoop::namenode", array("format" => "true"));
    $this->addDependency("hdp-hadoop::namenode", SERVICE_STATE_RUNNING, "hdp-hadoop::namenode::service_check", array());

    //MapReduce
    $this->addDependency("hdp-hadoop::jobtracker", SERVICE_STATE_RUNNING, "hdp-hadoop::jobtracker::service_check", array());

    //ZooKeeper
    $this->addDependency("hdp-zookeeper", SERVICE_STATE_RUNNING, "hdp-zookeeper::zookeeper::service_check", array());

    //HBase
    $this->addDependency("hdp-hbase::master", SERVICE_STATE_INSTALLED_AND_CONFIGURED, "hdp-zookeeper::client",
        array("service_state" => SERVICE_STATE_INSTALLED_AND_CONFIGURED));
    $this->addDependency("hdp-hbase::master", SERVICE_STATE_INSTALLED_AND_CONFIGURED, "hdp-hadoop::client",
        array("service_state" => SERVICE_STATE_INSTALLED_AND_CONFIGURED));

    $this->addDependency("hdp-hbase::master", SERVICE_STATE_RUNNING, "hdp-zookeeper::client",
        array("service_state" => SERVICE_STATE_NO_OP));
    $this->addDependency("hdp-hbase::master", SERVICE_STATE_RUNNING, "hdp-hadoop::client",
        array("service_state" => SERVICE_STATE_NO_OP));

    $this->addDependency("hdp-hbase::regionserver", SERVICE_STATE_INSTALLED_AND_CONFIGURED, "hdp-zookeeper::client",
        array("service_state" => SERVICE_STATE_INSTALLED_AND_CONFIGURED));
    $this->addDependency("hdp-hbase::regionserver", SERVICE_STATE_INSTALLED_AND_CONFIGURED, "hdp-hadoop::client",
        array("service_state" => SERVICE_STATE_INSTALLED_AND_CONFIGURED));

    $this->addDependency("hdp-hbase::regionserver", SERVICE_STATE_RUNNING, "hdp-zookeeper::client",
        array("service_state" => SERVICE_STATE_NO_OP));
    $this->addDependency("hdp-hbase::regionserver", SERVICE_STATE_RUNNING, "hdp-hadoop::client",
        array("service_state" => SERVICE_STATE_NO_OP));

    //Oozie
    $this->addDependency("hdp-oozie::server", SERVICE_STATE_INSTALLED_AND_CONFIGURED, "hdp-hadoop::client",
        array("service_state" => SERVICE_STATE_INSTALLED_AND_CONFIGURED));
    $this->addDependency("hdp-oozie::server", SERVICE_STATE_RUNNING, "hdp-hadoop::client",
        array("service_state" => SERVICE_STATE_NO_OP));

    //Hive depends on Hcat
    $this->addDependency("hdp-hive::server", SERVICE_STATE_INSTALLED_AND_CONFIGURED, "hdp-hcat",
        array("service_state" => SERVICE_STATE_INSTALLED_AND_CONFIGURED));
    $this->addDependency("hdp-hive::server", SERVICE_STATE_RUNNING, "hdp-hcat",
        array("service_state" => SERVICE_STATE_NO_OP));
    $this->addDependency("hdp-hive::client", SERVICE_STATE_INSTALLED_AND_CONFIGURED, "hdp-hcat",
        array("service_state" => SERVICE_STATE_INSTALLED_AND_CONFIGURED));

    //Hive Service Check
    $this->addDependency("hdp-hive::hive::service_check", SERVICE_STATE_NOT_APPLICABLE, 
        "hdp-hcat::hcat::service_check", array());

    //Pig
    $this->addDependency("hdp-pig", SERVICE_STATE_INSTALLED_AND_CONFIGURED, "hdp-hadoop::client",
        array("service_state" => SERVICE_STATE_INSTALLED_AND_CONFIGURED));

    //Sqoop
    $this->addDependency("hdp-sqoop", SERVICE_STATE_INSTALLED_AND_CONFIGURED, "hdp-hadoop::client",
        array("service_state" => SERVICE_STATE_INSTALLED_AND_CONFIGURED));

    //Templeton
    $this->addDependency("hdp-templeton::server", SERVICE_STATE_INSTALLED_AND_CONFIGURED, "hdp-zookeeper::client",
        array("service_state" => SERVICE_STATE_INSTALLED_AND_CONFIGURED));
    $this->addDependency("hdp-templeton::server", SERVICE_STATE_INSTALLED_AND_CONFIGURED, "hdp-hadoop::client",
        array("service_state" => SERVICE_STATE_INSTALLED_AND_CONFIGURED));
    $this->addDependency("hdp-templeton::server", SERVICE_STATE_INSTALLED_AND_CONFIGURED, "hdp-pig",
        array("service_state" => SERVICE_STATE_INSTALLED_AND_CONFIGURED));
    $this->addDependency("hdp-templeton::server", SERVICE_STATE_INSTALLED_AND_CONFIGURED, "hdp-hcat",
        array("service_state" => SERVICE_STATE_INSTALLED_AND_CONFIGURED));

    $this->addDependency("hdp-templeton::server", SERVICE_STATE_RUNNING, "hdp-zookeeper::client",
        array("service_state" => SERVICE_STATE_NO_OP));
    $this->addDependency("hdp-templeton::server", SERVICE_STATE_RUNNING, "hdp-hadoop::client",
        array("service_state" => SERVICE_STATE_NO_OP));
    $this->addDependency("hdp-templeton::server", SERVICE_STATE_RUNNING, "hdp-pig",
        array("service_state" => SERVICE_STATE_NO_OP));
    $this->addDependency("hdp-templeton::server", SERVICE_STATE_RUNNING, "hdp-hcat",
        array("service_state" => SERVICE_STATE_NO_OP));

    //Ganglia
    $this->addDependency("hdp-ganglia::monitor", SERVICE_STATE_RUNNING, "hdp-ganglia::hdp-gmond::service_check", array());
    $this->addDependency("hdp-ganglia::monitor_and_server", SERVICE_STATE_RUNNING, "hdp-ganglia::hdp-gmond::service_check", array());
    $this->addDependency("hdp-ganglia::monitor_and_server", SERVICE_STATE_RUNNING, "hdp-ganglia::hdp-gmetad::service_check", array());
    $this->addDependency("hdp-ganglia::monitor_and_server", SERVICE_STATE_RUNNING, "hdp-monitor-webserver", array());

    //Nagios
    $this->addDependency("hdp-nagios::server", SERVICE_STATE_RUNNING, "hdp-nagios::nagios::service_check", array());
    $this->addDependency("hdp-nagios::server", SERVICE_STATE_RUNNING, "hdp-monitor-webserver", array());
    $this->addDependency("hdp-nagios::server", SERVICE_STATE_RUNNING, "hdp-oozie::client",
        array("service_state" => SERVICE_STATE_INSTALLED_AND_CONFIGURED));
    $this->addDependency("hdp-nagios::server", SERVICE_STATE_RUNNING, "hdp-hcat",
        array("service_state" => SERVICE_STATE_INSTALLED_AND_CONFIGURED));

    //Dashboard
    $this->addDependency("hdp-dashboard", SERVICE_STATE_RUNNING, "hdp-dashboard::dashboard::service_check", array());
  }

  private function addDependency($role, $serviceState, $dep, $states) {
    if (!isset($this->hostLevelDependencies[$role])) {
      $this->hostLevelDependencies[$role] = array();
    }
    if (!isset($this->hostLevelDependencies[$role][$serviceState])) {
      $this->hostLevelDependencies[$role][$serviceState] = array();
    }
    if (!isset($this->hostLevelDependencies[$role][$serviceState][$dep])) {
      $this->hostLevelDependencies[$role][$serviceState][$dep] = $states;
    } else {
      $prevStates = $this->hostLevelDependencies[$role][$serviceState][$dep];
      foreach ($states as $k => $v) {
        $prevStates[$k] = $v;
      }
      $this->hostLevelDependencies[$role][$serviceState][$dep] = $prevStates;
    }
  }

  public function getHostLevelDependencies($role, $serviceState) {
    $dependencies = array("hdp" => array() );
    if (!isset($this->hostLevelDependencies[$role][$serviceState])) {
      return $dependencies;
    }
    $deps = $this->hostLevelDependencies[$role][$serviceState];
    if (empty($deps)) {
      return $dependencies;
    }
    $reqs = array_keys($deps);
    while (!(empty($reqs))) {
      $d = array_pop($reqs);
      $moreReqs;
      if(isset($this->hostLevelDependencies[$d])) {
        $depSvcState = SERVICE_STATE_NOT_APPLICABLE;
        if (isset($this->hostLevelDependencies[$d][SERVICE_STATE_KEY])) {
          $depSvcState = $this->hostLevelDependencies[$d][SERVICE_STATE_KEY];
        }
        $moreReqs = NULL;
        if (isset($this->hostLevelDependencies[$d][$depSvcState])) {
          $moreReqs = $this->hostLevelDependencies[$d][$depSvcState];
        }
        if (!empty($moreReqs)) {
          foreach($moreReqs as $r => $v) {
            $deps[$r] = $v;
            if (!in_array($r, $reqs)) {
              array_push($reqs, $r);
            }
          }
        }
      }
      $dependencies[$d] = $deps[$d];
    }
    return $dependencies;
  }

}
?>
