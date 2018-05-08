-- MySQL dump 10.13  Distrib 5.5.56, for Linux (x86_64)
--
-- Host: localhost    Database: jiaoben
-- ------------------------------------------------------
-- Server version	5.5.56-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cy_admin`
--

DROP TABLE IF EXISTS `cy_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cy_admin` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `username` varchar(30) NOT NULL DEFAULT '' COMMENT '登录用户名',
  `password` varchar(40) NOT NULL DEFAULT '' COMMENT '登录密码',
  `department_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'spdepartment表中的id',
  `status` smallint(2) NOT NULL DEFAULT '0' COMMENT '0为正常状态，1为封停状态',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `beizhu` varchar(20) NOT NULL DEFAULT '' COMMENT '备注名',
  `login_check` tinyint(3) DEFAULT NULL,
  `mobile` varchar(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='管理员用户表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cy_admin`
--

LOCK TABLES `cy_admin` WRITE;
/*!40000 ALTER TABLE `cy_admin` DISABLE KEYS */;
INSERT INTO `cy_admin` VALUES (2,'admin','08e63c455faf6f0d9209eb771bc81e2f',11,1,0,'超级管理员',NULL,NULL,NULL),(4,'chpddd','08e63c455faf6f0d9209eb771bc81e2f',11,1,1512891326,'',NULL,NULL,NULL),(5,'13477896660','885ec171951667b0794c9d880f648d75',11,1,1513840690,'',NULL,NULL,NULL),(6,'xiaopeng','0e4fe7c0294127f88861a9a17dc69267',11,1,1519883743,'',NULL,NULL,NULL);
/*!40000 ALTER TABLE `cy_admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `type`
--

DROP TABLE IF EXISTS `type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `type` (
  `typeid` int(11) NOT NULL AUTO_INCREMENT,
  `typename` varchar(32) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  PRIMARY KEY (`typeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `type`
--

LOCK TABLES `type` WRITE;
/*!40000 ALTER TABLE `type` DISABLE KEYS */;
/*!40000 ALTER TABLE `type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `typedata`
--

DROP TABLE IF EXISTS `typedata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `typedata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` varchar(200) DEFAULT NULL,
  `tid` int(11) DEFAULT NULL,
  `orderid` int(11) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `creattime` int(11) DEFAULT NULL,
  `updatetime` int(11) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `typedata`
--

LOCK TABLES `typedata` WRITE;
/*!40000 ALTER TABLE `typedata` DISABLE KEYS */;
/*!40000 ALTER TABLE `typedata` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-05-08  9:43:11
