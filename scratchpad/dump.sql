-- MySQL dump 10.13  Distrib 5.7.22, for Linux (x86_64)
--
-- Host: localhost    Database: commonsbooking_2
-- ------------------------------------------------------
-- Server version	5.7.22-0ubuntu0.17.10.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `wp_cb2_global_period_groups`
--

DROP TABLE IF EXISTS `wp_cb2_global_period_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_cb2_global_period_groups` (
  `global_period_group_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `period_group_id` int(11) unsigned NOT NULL,
  `period_status_type_id` int(11) unsigned NOT NULL,
  `remove` bit(1) DEFAULT b'0',
  PRIMARY KEY (`global_period_group_id`),
  UNIQUE KEY `global_item_id_UNIQUE` (`global_period_group_id`),
  KEY `period_group_id_idx` (`period_group_id`),
  KEY `fk_wp_cb2_global_period_groups_2_idx` (`period_status_type_id`)
) ENGINE=InnoDB;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_cb2_global_period_groups`
--

LOCK TABLES `wp_cb2_global_period_groups` WRITE;
/*!40000 ALTER TABLE `wp_cb2_global_period_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_cb2_global_period_groups` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_global_period_groups_AFTER_INSERT` AFTER INSERT ON `wp_cb2_global_period_groups` FOR EACH ROW
BEGIN
	# Deleting from wp_postmeta without meta_id
    set @safe_updates = @@sql_safe_updates; 
    set @@sql_safe_updates = 0;
    
	# ----------------------------- perioditem(s)
	# Remove all existing metadata
	delete from wp_postmeta 
		where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = new.global_period_group_id
            and post_type = 'perioditem-global'
        );
    
    # ReCreate all metadata
    insert into wp_postmeta( meta_id, post_id, meta_key, meta_value )
		select meta_id, post_id, meta_key, meta_value 
        from wp_cb2_view_perioditemmeta
        where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = new.global_period_group_id
            and post_type = 'perioditem-global'
        );

    SET @@sql_safe_updates = @safe_updates;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_global_period_groups_AFTER_UPDATE` AFTER UPDATE ON `wp_cb2_global_period_groups` FOR EACH ROW
BEGIN
	# Deleting from wp_postmeta without meta_id
    set @safe_updates = @@sql_safe_updates; 
    set @@sql_safe_updates = 0;
    
	# ----------------------------- perioditem(s)
	# Remove all existing metadata
	delete from wp_postmeta 
		where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = new.global_period_group_id
            and post_type = 'perioditem-global'
        );
    
    # ReCreate all metadata
    insert into wp_postmeta( meta_id, post_id, meta_key, meta_value )
		select meta_id, post_id, meta_key, meta_value 
        from wp_cb2_view_perioditemmeta
        where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = new.global_period_group_id
            and post_type = 'perioditem-global'
        );

    SET @@sql_safe_updates = @safe_updates;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_global_period_groups_BEFORE_DELETE` BEFORE DELETE ON `wp_cb2_global_period_groups` FOR EACH ROW
BEGIN
	# ----------------------------- perioditem(s)
	# Remove all existing metadata
	delete from wp_postmeta 
		where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = old.global_period_group_id
            and post_type = 'perioditem-global'
        );

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `wp_cb2_location_period_groups`
--

DROP TABLE IF EXISTS `wp_cb2_location_period_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_cb2_location_period_groups` (
  `location_period_group_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_ID` bigint(20) unsigned NOT NULL,
  `period_group_id` int(11) unsigned NOT NULL,
  `period_status_type_id` int(11) unsigned NOT NULL,
  `remove` bit(1) DEFAULT b'0',
  PRIMARY KEY (`location_ID`,`period_group_id`),
  UNIQUE KEY `location_item_id_UNIQUE` (`location_period_group_id`),
  KEY `fk_wp_cb2_location_period_groups_2_idx` (`period_group_id`),
  KEY `fk_wp_cb2_location_period_groups_3_idx` (`period_status_type_id`),
  CONSTRAINT `fk_wp_cb2_location_period_groups_1` FOREIGN KEY (`location_ID`) REFERENCES `wp_posts` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_wp_cb2_location_period_groups_2` FOREIGN KEY (`period_group_id`) REFERENCES `wp_cb2_period_groups` (`period_group_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_wp_cb2_location_period_groups_3` FOREIGN KEY (`period_status_type_id`) REFERENCES `wp_cb2_period_status_types` (`period_status_type_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_cb2_location_period_groups`
--

LOCK TABLES `wp_cb2_location_period_groups` WRITE;
/*!40000 ALTER TABLE `wp_cb2_location_period_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_cb2_location_period_groups` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_location_period_groups_AFTER_INSERT` AFTER INSERT ON `wp_cb2_location_period_groups` FOR EACH ROW
BEGIN
	# Deleting from wp_postmeta without meta_id
    set @safe_updates = @@sql_safe_updates; 
    set @@sql_safe_updates = 0;
    
	# ----------------------------- perioditem(s)
	# Remove all existing metadata
	delete from wp_postmeta 
		where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = new.location_period_group_id
            and post_type = 'perioditem-location'
        );
    
    # ReCreate all metadata
    insert into wp_postmeta( meta_id, post_id, meta_key, meta_value )
		select meta_id, post_id, meta_key, meta_value 
        from wp_cb2_view_perioditemmeta
        where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = new.location_period_group_id
            and post_type = 'perioditem-location'
        );

    SET @@sql_safe_updates = @safe_updates;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_location_period_groups_AFTER_UPDATE` AFTER UPDATE ON `wp_cb2_location_period_groups` FOR EACH ROW
BEGIN
	# Deleting from wp_postmeta without meta_id
    set @safe_updates = @@sql_safe_updates; 
    set @@sql_safe_updates = 0;
    
	# ----------------------------- perioditem(s)
	# Remove all existing metadata
	delete from wp_postmeta 
		where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = new.location_period_group_id
            and post_type = 'perioditem-location'
        );
    
    # ReCreate all metadata
    insert into wp_postmeta( meta_id, post_id, meta_key, meta_value )
		select meta_id, post_id, meta_key, meta_value 
        from wp_cb2_view_perioditemmeta
        where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = new.location_period_group_id
            and post_type = 'perioditem-location'
        );

    SET @@sql_safe_updates = @safe_updates;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_location_period_groups_BEFORE_DELETE` BEFORE DELETE ON `wp_cb2_location_period_groups` FOR EACH ROW
BEGIN
	# ----------------------------- perioditem(s)
	# Remove all existing metadata
	delete from wp_postmeta 
		where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = old.location_period_group_id
            and post_type = 'perioditem-location'
        );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `wp_cb2_period_group_period`
--

DROP TABLE IF EXISTS `wp_cb2_period_group_period`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_cb2_period_group_period` (
  `period_group_id` int(11) unsigned NOT NULL,
  `period_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`period_group_id`,`period_id`),
  KEY `period_id_idx` (`period_id`),
  CONSTRAINT `period_group_id_1` FOREIGN KEY (`period_group_id`) REFERENCES `wp_cb2_period_groups` (`period_group_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_cb2_period_group_period`
--

LOCK TABLES `wp_cb2_period_group_period` WRITE;
/*!40000 ALTER TABLE `wp_cb2_period_group_period` DISABLE KEYS */;
INSERT INTO `wp_cb2_period_group_period` VALUES (20,10);
/*!40000 ALTER TABLE `wp_cb2_period_group_period` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_cb2_period_groups`
--

DROP TABLE IF EXISTS `wp_cb2_period_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_cb2_period_groups` (
  `period_group_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(1024) NOT NULL DEFAULT 'period group',
  `description` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`period_group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_cb2_period_groups`
--

LOCK TABLES `wp_cb2_period_groups` WRITE;
/*!40000 ALTER TABLE `wp_cb2_period_groups` DISABLE KEYS */;
INSERT INTO `wp_cb2_period_groups` VALUES (20,'test',NULL),(21,'fgdfghdfgh',NULL);
/*!40000 ALTER TABLE `wp_cb2_period_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_cb2_period_status_types`
--

DROP TABLE IF EXISTS `wp_cb2_period_status_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_cb2_period_status_types` (
  `period_status_type_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(1024) NOT NULL,
  `description` varchar(1024) DEFAULT NULL,
  `flags` bit(32) NOT NULL DEFAULT b'0',
  `colour` char(6) DEFAULT NULL,
  `opacity` tinyint(1) NOT NULL DEFAULT '100',
  `priority` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`period_status_type_id`),
  UNIQUE KEY `timeframe_id_UNIQUE` (`period_status_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_cb2_period_status_types`
--

LOCK TABLES `wp_cb2_period_status_types` WRITE;
/*!40000 ALTER TABLE `wp_cb2_period_status_types` DISABLE KEYS */;
INSERT INTO `wp_cb2_period_status_types` VALUES (1,'available','','b\'0\'','test',100,1),(2,'booked',NULL,'\0\0\0\0','dd3333',50,2),(3,'closed','rrr','\0\053',NULL,50,3),(4,'open','','\0\049',NULL,100,3),(5,'repair',NULL,'\0\0\0\0',NULL,100,4),(6,'holiday',' ','\0\0\00','gray',100,6),(8,'party','and','ÿÿÿÿ','red',100,5);
/*!40000 ALTER TABLE `wp_cb2_period_status_types` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_period_status_types_BEFORE_UPDATE` BEFORE UPDATE ON `wp_cb2_period_status_types` FOR EACH ROW
BEGIN
	if old.period_status_type_id <= 6 then
		if not old.period_status_type_id = new.period_status_type_id then
			signal sqlstate '45000' set message_text = 'system period status type IDs cannot be updated';
		end if;
		if not old.name = new.name then
			signal sqlstate '45001' set message_text = 'system period status type names cannot be updated';
		end if;
	end if;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_period_status_types_BEFORE_DELETE` BEFORE DELETE ON `wp_cb2_period_status_types` FOR EACH ROW
BEGIN
	if old.period_status_type_id <= 6 then
		signal sqlstate '45000' set message_text = 'system period status types cannot be removed';
	end if;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `wp_cb2_periods`
--

DROP TABLE IF EXISTS `wp_cb2_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_cb2_periods` (
  `period_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(1024) NOT NULL DEFAULT 'period',
  `description` varchar(2048) DEFAULT NULL,
  `datetime_part_period_start` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Only part of this datetime may be used, depending on the recurrence_type',
  `datetime_part_period_end` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Only part of this datetime may be used, depending on the recurrence_type',
  `recurrence_type` char(1) DEFAULT NULL COMMENT 'recurrence_type:\nNULL - no recurrence\nD - daily recurrence (start and end time parts used only)\nW - weekly recurrence (day-of-week and start and end time parts used only)\nM - monthly recurrence (day-of-month and start and end time parts used only)\nY - yearly recurrence (full absolute start and end time parts used)',
  `recurrence_frequency` int(11) NOT NULL DEFAULT '1' COMMENT 'e.g. Every 2 weeks',
  `datetime_from` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Absolute date: when the period should start appearing in the calendar',
  `datetime_to` datetime DEFAULT NULL COMMENT 'Absolute date: when the period should stop appearing in the calendar',
  `recurrence_sequence` bit(32) DEFAULT NULL,
  PRIMARY KEY (`period_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_cb2_periods`
--

LOCK TABLES `wp_cb2_periods` WRITE;
/*!40000 ALTER TABLE `wp_cb2_periods` DISABLE KEYS */;
INSERT INTO `wp_cb2_periods` VALUES (10,'test',NULL,'2018-09-03 00:00:00','2018-09-04 00:00:00','W',1,'2018-09-03 00:00:00',NULL,NULL),(11,'fgdfghdfgh',NULL,'2018-09-04 00:00:00','2018-09-05 00:00:00',NULL,1,'2018-09-04 00:00:00',NULL,NULL);
/*!40000 ALTER TABLE `wp_cb2_periods` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_periods_BEFORE_INSERT` 
BEFORE INSERT ON `wp_cb2_periods` FOR EACH ROW
BEGIN
	if new.recurrence_type not in('Y', 'M', 'W', 'D') then
		signal sqlstate '45000' set message_text = 'recurrence_type must be one of Y,M,W,D';
	end if;

	if not new.datetime_to is null and new.datetime_to < new.datetime_from then
		signal sqlstate '45000' set message_text = 'datetime_to must be after datetime_from';
	end if;

	if new.datetime_part_period_end < new.datetime_part_period_start then
		signal sqlstate '45000' set message_text = 'datetime_part_period_end must be after datetime_part_period_start';
	end if;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_periods_AFTER_INSERT` AFTER INSERT ON `wp_cb2_periods` FOR EACH ROW
BEGIN
	# Deleting from wp_postmeta without meta_id
    set @safe_updates = @@sql_safe_updates; 
    set @@sql_safe_updates = 0;
    
	# ----------------------------- perioditem(s)
	# Remove all existing metadata
	delete from wp_postmeta 
		where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where period_id = new.period_id
        );
    
    # ReCreate all metadata
    insert into wp_postmeta( meta_id, post_id, meta_key, meta_value )
		select meta_id, post_id, meta_key, meta_value 
        from wp_cb2_view_perioditemmeta
        where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where period_id = new.period_id
        );

    SET @@sql_safe_updates = @safe_updates;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_periods_check_recurrence_type_BEFORE_UPDATE` 
BEFORE update ON `wp_cb2_periods` FOR EACH ROW
BEGIN
	if new.recurrence_type not in('Y', 'M', 'W', 'D') then
		signal sqlstate '45000' set message_text = 'recurrence_type must be one of Y,M,W,D';
	end if;

	if not new.datetime_to is null and  new.datetime_to < new.datetime_from then
		signal sqlstate '45000' set message_text = 'datetime_to must be after datetime_from';
	end if;

	if new.datetime_part_period_end < new.datetime_part_period_start then
		signal sqlstate '45000' set message_text = 'datetime_part_period_end must be after datetime_part_period_start';
	end if;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_periods_AFTER_UPDATE` AFTER UPDATE ON `wp_cb2_periods` FOR EACH ROW
BEGIN
	# Deleting from wp_postmeta without meta_id
    set @safe_updates = @@sql_safe_updates; 
    set @@sql_safe_updates = 0;
    
	# ----------------------------- perioditem(s)
	# Remove all existing metadata
	delete from wp_postmeta 
		where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where period_id = new.period_id
        );
    
    # ReCreate all metadata
    insert into wp_postmeta( meta_id, post_id, meta_key, meta_value )
		select meta_id, post_id, meta_key, meta_value 
        from wp_cb2_view_perioditemmeta
        where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where period_id = new.period_id
        );

    SET @@sql_safe_updates = @safe_updates;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_periods_BEFORE_DELETE` BEFORE DELETE ON `wp_cb2_periods` FOR EACH ROW
BEGIN
	# ----------------------------- perioditem(s)
	# Remove all existing metadata
	delete from wp_postmeta 
		where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where period_id = old.period_id
        );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `wp_cb2_post_types`
--

DROP TABLE IF EXISTS `wp_cb2_post_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_cb2_post_types` (
  `post_type_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `post_type` varchar(20) NOT NULL,
  `ID_base` bigint(20) unsigned NOT NULL,
  `ID_multiplier` bigint(20) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`post_type_id`),
  UNIQUE KEY `post_type_UNIQUE` (`post_type`),
  UNIQUE KEY `post_type_id_UNIQUE` (`post_type_id`),
  UNIQUE KEY `ID_base_UNIQUE` (`ID_base`)
) ENGINE=InnoDB AUTO_INCREMENT=16;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_cb2_post_types`
--

LOCK TABLES `wp_cb2_post_types` WRITE;
/*!40000 ALTER TABLE `wp_cb2_post_types` DISABLE KEYS */;
INSERT INTO `wp_cb2_post_types` VALUES (1,'period',200000000,1),(2,'periodgroup',800000000,1),(3,'perioditem-automatic',300000000,10000),(4,'perioditem-global',400000000,10000),(5,'perioditem-location',500000000,10000),(6,'perioditem-timeframe',600000000,10000),(7,'perioditem-user',700000000,10000),(8,'periodstatustype',100000000,1),(12,'periodent-global',900000000,1),(13,'periodent-location',1000000000,1),(14,'periodent-timeframe',1100000000,1),(15,'periodent-user',1200000000,1);
/*!40000 ALTER TABLE `wp_cb2_post_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_cb2_timeframe_options`
--

DROP TABLE IF EXISTS `wp_cb2_timeframe_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_cb2_timeframe_options` (
  `option_id` int(20) unsigned NOT NULL AUTO_INCREMENT,
  `timeframe_id` bigint(20) unsigned NOT NULL,
  `option_name` varchar(191) DEFAULT NULL,
  `option_value` longtext NOT NULL,
  PRIMARY KEY (`option_id`),
  KEY `fk_wp_cb2_timeframe_options_1_idx` (`timeframe_id`),
  CONSTRAINT `fk_wp_cb2_timeframe_options_1` FOREIGN KEY (`timeframe_id`) REFERENCES `wp_cb2_timeframe_period_groups` (`timeframe_period_group_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_cb2_timeframe_options`
--

LOCK TABLES `wp_cb2_timeframe_options` WRITE;
/*!40000 ALTER TABLE `wp_cb2_timeframe_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_cb2_timeframe_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_cb2_timeframe_period_groups`
--

DROP TABLE IF EXISTS `wp_cb2_timeframe_period_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_cb2_timeframe_period_groups` (
  `timeframe_period_group_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_ID` bigint(20) unsigned NOT NULL,
  `item_ID` bigint(20) unsigned NOT NULL,
  `period_group_id` int(11) unsigned NOT NULL,
  `period_status_type_id` int(11) unsigned NOT NULL,
  `remove` bit(1) DEFAULT NULL,
  PRIMARY KEY (`location_ID`,`item_ID`,`period_group_id`),
  UNIQUE KEY `timeframe_item_id_UNIQUE` (`timeframe_period_group_id`),
  KEY `fk_wp_cb2_location_item_period_groups_2_idx` (`item_ID`),
  KEY `fk_wp_cb2_location_item_period_groups_3_idx` (`period_group_id`),
  KEY `fk_wp_cb2_timeframe_period_groups_4_idx` (`period_status_type_id`),
  CONSTRAINT `fk_wp_cb2_timeframe_period_groups_1` FOREIGN KEY (`location_ID`) REFERENCES `wp_posts` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_wp_cb2_timeframe_period_groups_2` FOREIGN KEY (`item_ID`) REFERENCES `wp_posts` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_wp_cb2_timeframe_period_groups_3` FOREIGN KEY (`period_group_id`) REFERENCES `wp_cb2_period_groups` (`period_group_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_wp_cb2_timeframe_period_groups_4` FOREIGN KEY (`period_status_type_id`) REFERENCES `wp_cb2_period_status_types` (`period_status_type_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=11;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_cb2_timeframe_period_groups`
--

LOCK TABLES `wp_cb2_timeframe_period_groups` WRITE;
/*!40000 ALTER TABLE `wp_cb2_timeframe_period_groups` DISABLE KEYS */;
INSERT INTO `wp_cb2_timeframe_period_groups` VALUES (10,763,767,21,1,NULL),(9,765,768,20,1,NULL);
/*!40000 ALTER TABLE `wp_cb2_timeframe_period_groups` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_timeframe_period_groups_AFTER_INSERT` AFTER INSERT ON `wp_cb2_timeframe_period_groups` FOR EACH ROW
BEGIN
	# Deleting from wp_postmeta without meta_id
    set @safe_updates = @@sql_safe_updates; 
    set @@sql_safe_updates = 0;
    
	# ----------------------------- perioditem(s)
	# Remove all existing metadata
	delete from wp_postmeta 
		where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = new.timeframe_period_group_id
            and post_type = 'perioditem-timeframe'
        );
    
    # ReCreate all metadata
    insert into wp_postmeta( meta_id, post_id, meta_key, meta_value )
		select meta_id, post_id, meta_key, meta_value 
        from wp_cb2_view_perioditemmeta
        where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = new.timeframe_period_group_id
            and post_type = 'perioditem-timeframe'
        );

    SET @@sql_safe_updates = @safe_updates;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_timeframe_period_groups_AFTER_UPDATE` AFTER UPDATE ON `wp_cb2_timeframe_period_groups` FOR EACH ROW
BEGIN
	# Deleting from wp_postmeta without meta_id
    set @safe_updates = @@sql_safe_updates; 
    set @@sql_safe_updates = 0;
    
	# ----------------------------- perioditem(s)
	# Remove all existing metadata
	delete from wp_postmeta 
		where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = new.timeframe_period_group_id
            and post_type = 'perioditem-timeframe'
        );
    
    # ReCreate all metadata
    insert into wp_postmeta( meta_id, post_id, meta_key, meta_value )
		select meta_id, post_id, meta_key, meta_value 
        from wp_cb2_view_perioditemmeta
        where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = new.timeframe_period_group_id
            and post_type = 'perioditem-timeframe'
        );

    SET @@sql_safe_updates = @safe_updates;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_timeframe_period_groups_BEFORE_DELETE` BEFORE DELETE ON `wp_cb2_timeframe_period_groups` FOR EACH ROW
BEGIN
	# ----------------------------- perioditem(s)
	# Remove all existing metadata
	delete from wp_postmeta 
		where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = old.timeframe_period_group_id
            and post_type = 'perioditem-timeframe'
        );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `wp_cb2_timeframe_user_period_groups`
--

DROP TABLE IF EXISTS `wp_cb2_timeframe_user_period_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_cb2_timeframe_user_period_groups` (
  `timeframe_user_period_group_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_ID` bigint(20) unsigned NOT NULL,
  `item_ID` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `period_group_id` int(11) unsigned NOT NULL,
  `remove` bit(1) DEFAULT NULL,
  `period_status_type_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`location_ID`,`item_ID`,`user_id`,`period_group_id`),
  UNIQUE KEY `timeframe_id_UNIQUE` (`timeframe_user_period_group_id`),
  KEY `fk_wp_cb2_location_item_user_period_groups_2_idx` (`item_ID`),
  KEY `fk_wp_cb2_location_item_user_period_groups_3_idx` (`user_id`),
  KEY `fk_wp_cb2_location_item_user_period_groups_4_idx` (`period_group_id`),
  KEY `fk_wp_cb2_timeframe_user_period_groups_1_idx` (`period_status_type_id`),
  CONSTRAINT `fk_wp_cb2_location_item_user_period_groups_1` FOREIGN KEY (`location_ID`) REFERENCES `wp_posts` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_wp_cb2_location_item_user_period_groups_2` FOREIGN KEY (`item_ID`) REFERENCES `wp_posts` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_wp_cb2_location_item_user_period_groups_3` FOREIGN KEY (`user_id`) REFERENCES `wp_users` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_wp_cb2_timeframe_user_period_groups_1` FOREIGN KEY (`period_status_type_id`) REFERENCES `wp_cb2_period_status_types` (`period_status_type_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_wp_cb2_timeframe_user_period_groups_2` FOREIGN KEY (`period_group_id`) REFERENCES `wp_cb2_period_status_types` (`period_status_type_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_cb2_timeframe_user_period_groups`
--

LOCK TABLES `wp_cb2_timeframe_user_period_groups` WRITE;
/*!40000 ALTER TABLE `wp_cb2_timeframe_user_period_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_cb2_timeframe_user_period_groups` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_timeframe_user_period_groups_AFTER_INSERT` AFTER INSERT ON `wp_cb2_timeframe_user_period_groups` FOR EACH ROW
BEGIN
	# Deleting from wp_postmeta without meta_id
    set @safe_updates = @@sql_safe_updates; 
    set @@sql_safe_updates = 0;
    
	# ----------------------------- perioditem(s)
	# Remove all existing metadata
	delete from wp_postmeta 
		where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = new.timeframe_user_period_group_id
            and post_type = 'perioditem-user'
        );
    
    # ReCreate all metadata
    insert into wp_postmeta( meta_id, post_id, meta_key, meta_value )
		select meta_id, post_id, meta_key, meta_value 
        from wp_cb2_view_perioditemmeta
        where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = new.timeframe_user_period_group_id
            and post_type = 'perioditem-user'
        );

    SET @@sql_safe_updates = @safe_updates;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_timeframe_user_period_groups_AFTER_UPDATE` AFTER UPDATE ON `wp_cb2_timeframe_user_period_groups` FOR EACH ROW
BEGIN
	# Deleting from wp_postmeta without meta_id
    set @safe_updates = @@sql_safe_updates; 
    set @@sql_safe_updates = 0;
    
	# ----------------------------- perioditem(s)
	# Remove all existing metadata
	delete from wp_postmeta 
		where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = new.timeframe_user_period_group_id
            and post_type = 'perioditem-user'
        );
    
    # ReCreate all metadata
    insert into wp_postmeta( meta_id, post_id, meta_key, meta_value )
		select meta_id, post_id, meta_key, meta_value 
        from wp_cb2_view_perioditemmeta
        where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = new.timeframe_user_period_group_id
            and post_type = 'perioditem-user'
        );

    SET @@sql_safe_updates = @safe_updates;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `wp_cb2_timeframe_user_period_groups_BEFORE_DELETE` BEFORE DELETE ON `wp_cb2_timeframe_user_period_groups` FOR EACH ROW
BEGIN
	# ----------------------------- perioditem(s)
	# Remove all existing metadata
	delete from wp_postmeta 
		where post_id in(
			select ID from wp_cb2_view_perioditem_posts
            where timeframe_id = old.timeframe_user_period_group_id
            and post_type = 'perioditem-user'
        );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Temporary table structure for view `wp_cb2_view_future_bookings`
--

DROP TABLE IF EXISTS `wp_cb2_view_future_bookings`;
/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_future_bookings`*/;
SET @saved_cs_client     = @@character_set_client;

/*!50001 CREATE VIEW `wp_cb2_view_future_bookings` AS SELECT 
 1 AS `timeframe_id`,
 1 AS `period_id`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `wp_cb2_view_period_entities`
--

DROP TABLE IF EXISTS `wp_cb2_view_period_entities`;
/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_period_entities`*/;
SET @saved_cs_client     = @@character_set_client;

/*!50001 CREATE VIEW `wp_cb2_view_period_entities` AS SELECT 
 1 AS `timeframe_id`,
 1 AS `title`,
 1 AS `location_ID`,
 1 AS `item_ID`,
 1 AS `user_ID`,
 1 AS `period_group_type`,
 1 AS `period_group_priority`,
 1 AS `period_group_id`,
 1 AS `period_status_type_id`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `wp_cb2_view_period_posts`
--

DROP TABLE IF EXISTS `wp_cb2_view_period_posts`;
/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_period_posts`*/;
SET @saved_cs_client     = @@character_set_client;

/*!50001 CREATE VIEW `wp_cb2_view_period_posts` AS SELECT 
 1 AS `ID`,
 1 AS `post_author`,
 1 AS `post_date`,
 1 AS `post_date_gmt`,
 1 AS `post_content`,
 1 AS `post_title`,
 1 AS `post_excerpt`,
 1 AS `post_status`,
 1 AS `comment_status`,
 1 AS `ping_status`,
 1 AS `post_password`,
 1 AS `post_name`,
 1 AS `to_ping`,
 1 AS `pinged`,
 1 AS `post_modified`,
 1 AS `post_modified_gmt`,
 1 AS `post_content_filtered`,
 1 AS `post_parent`,
 1 AS `guid`,
 1 AS `menu_order`,
 1 AS `post_type`,
 1 AS `post_mime_type`,
 1 AS `comment_count`,
 1 AS `period_id`,
 1 AS `datetime_part_period_start`,
 1 AS `datetime_part_period_end`,
 1 AS `datetime_from`,
 1 AS `datetime_to`,
 1 AS `recurrence_type`,
 1 AS `recurrence_sequence`,
 1 AS `recurrence_frequency`,
 1 AS `period_group_IDs`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `wp_cb2_view_periodent_posts`
--

DROP TABLE IF EXISTS `wp_cb2_view_periodent_posts`;
/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_periodent_posts`*/;
SET @saved_cs_client     = @@character_set_client;

/*!50001 CREATE VIEW `wp_cb2_view_periodent_posts` AS SELECT 
 1 AS `ID`,
 1 AS `post_author`,
 1 AS `post_date`,
 1 AS `post_date_gmt`,
 1 AS `post_content`,
 1 AS `post_title`,
 1 AS `post_excerpt`,
 1 AS `post_status`,
 1 AS `comment_status`,
 1 AS `ping_status`,
 1 AS `post_password`,
 1 AS `post_name`,
 1 AS `to_ping`,
 1 AS `pinged`,
 1 AS `post_modified`,
 1 AS `post_modified_gmt`,
 1 AS `post_content_filtered`,
 1 AS `post_parent`,
 1 AS `guid`,
 1 AS `menu_order`,
 1 AS `post_type`,
 1 AS `post_mime_type`,
 1 AS `comment_count`,
 1 AS `timeframe_id`,
 1 AS `location_ID`,
 1 AS `item_ID`,
 1 AS `user_ID`,
 1 AS `period_group_ID`,
 1 AS `period_status_type_ID`,
 1 AS `period_IDs`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `wp_cb2_view_periodentmeta`
--

DROP TABLE IF EXISTS `wp_cb2_view_periodentmeta`;
/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_periodentmeta`*/;
SET @saved_cs_client     = @@character_set_client;

/*!50001 CREATE VIEW `wp_cb2_view_periodentmeta` AS SELECT 
 1 AS `meta_id`,
 1 AS `post_id`,
 1 AS `periodent_id`,
 1 AS `meta_key`,
 1 AS `meta_value`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `wp_cb2_view_periodgroup_posts`
--

DROP TABLE IF EXISTS `wp_cb2_view_periodgroup_posts`;
/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_periodgroup_posts`*/;
SET @saved_cs_client     = @@character_set_client;

/*!50001 CREATE VIEW `wp_cb2_view_periodgroup_posts` AS SELECT 
 1 AS `ID`,
 1 AS `post_author`,
 1 AS `post_date`,
 1 AS `post_date_gmt`,
 1 AS `post_content`,
 1 AS `post_title`,
 1 AS `post_excerpt`,
 1 AS `post_status`,
 1 AS `comment_status`,
 1 AS `ping_status`,
 1 AS `post_password`,
 1 AS `post_name`,
 1 AS `to_ping`,
 1 AS `pinged`,
 1 AS `post_modified`,
 1 AS `post_modified_gmt`,
 1 AS `post_content_filtered`,
 1 AS `post_parent`,
 1 AS `guid`,
 1 AS `menu_order`,
 1 AS `post_type`,
 1 AS `post_mime_type`,
 1 AS `comment_count`,
 1 AS `period_group_id`,
 1 AS `period_IDs`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `wp_cb2_view_periodgroupmeta`
--

DROP TABLE IF EXISTS `wp_cb2_view_periodgroupmeta`;
/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_periodgroupmeta`*/;
SET @saved_cs_client     = @@character_set_client;

/*!50001 CREATE VIEW `wp_cb2_view_periodgroupmeta` AS SELECT 
 1 AS `meta_id`,
 1 AS `post_id`,
 1 AS `periodgroup_id`,
 1 AS `meta_key`,
 1 AS `meta_value`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `wp_cb2_view_perioditem_entities`
--

DROP TABLE IF EXISTS `wp_cb2_view_perioditem_entities`;
/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_perioditem_entities`*/;
SET @saved_cs_client     = @@character_set_client;

/*!50001 CREATE VIEW `wp_cb2_view_perioditem_entities` AS SELECT 
 1 AS `timeframe_id`,
 1 AS `title`,
 1 AS `location_ID`,
 1 AS `item_ID`,
 1 AS `user_ID`,
 1 AS `period_group_type`,
 1 AS `period_group_priority`,
 1 AS `period_group_id`,
 1 AS `period_id`,
 1 AS `period_status_type_id`,
 1 AS `recurrence_index`,
 1 AS `datetime_period_item_start`,
 1 AS `datetime_period_item_end`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `wp_cb2_view_perioditem_posts`
--

DROP TABLE IF EXISTS `wp_cb2_view_perioditem_posts`;
/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_perioditem_posts`*/;
SET @saved_cs_client     = @@character_set_client;

/*!50001 CREATE VIEW `wp_cb2_view_perioditem_posts` AS SELECT 
 1 AS `ID`,
 1 AS `post_author`,
 1 AS `post_date`,
 1 AS `post_date_gmt`,
 1 AS `post_content`,
 1 AS `post_title`,
 1 AS `post_excerpt`,
 1 AS `post_status`,
 1 AS `comment_status`,
 1 AS `ping_status`,
 1 AS `post_password`,
 1 AS `post_name`,
 1 AS `to_ping`,
 1 AS `pinged`,
 1 AS `post_modified`,
 1 AS `post_modified_gmt`,
 1 AS `post_content_filtered`,
 1 AS `post_parent`,
 1 AS `guid`,
 1 AS `menu_order`,
 1 AS `post_type`,
 1 AS `post_mime_type`,
 1 AS `comment_count`,
 1 AS `period_group_id`,
 1 AS `period_id`,
 1 AS `recurrence_index`,
 1 AS `timeframe_id`,
 1 AS `period_entity_ID`,
 1 AS `location_ID`,
 1 AS `item_ID`,
 1 AS `user_ID`,
 1 AS `period_status_type_id`,
 1 AS `datetime_period_item_start`,
 1 AS `datetime_period_item_end`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `wp_cb2_view_perioditemmeta`
--

DROP TABLE IF EXISTS `wp_cb2_view_perioditemmeta`;
/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_perioditemmeta`*/;
SET @saved_cs_client     = @@character_set_client;

/*!50001 CREATE VIEW `wp_cb2_view_perioditemmeta` AS SELECT 
 1 AS `timeframe_id`,
 1 AS `recurrence_index`,
 1 AS `meta_id`,
 1 AS `post_id`,
 1 AS `perioditem_id`,
 1 AS `meta_key`,
 1 AS `meta_value`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `wp_cb2_view_perioditems`
--

DROP TABLE IF EXISTS `wp_cb2_view_perioditems`;
/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_perioditems`*/;
SET @saved_cs_client     = @@character_set_client;

/*!50001 CREATE VIEW `wp_cb2_view_perioditems` AS SELECT 
 1 AS `period_id`,
 1 AS `recurrence_index`,
 1 AS `datetime_period_item_start`,
 1 AS `datetime_period_item_end`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `wp_cb2_view_periodmeta`
--

DROP TABLE IF EXISTS `wp_cb2_view_periodmeta`;
/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_periodmeta`*/;
SET @saved_cs_client     = @@character_set_client;

/*!50001 CREATE VIEW `wp_cb2_view_periodmeta` AS SELECT 
 1 AS `meta_id`,
 1 AS `post_id`,
 1 AS `period_id`,
 1 AS `meta_key`,
 1 AS `meta_value`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `wp_cb2_view_periodstatustype_posts`
--

DROP TABLE IF EXISTS `wp_cb2_view_periodstatustype_posts`;
/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_periodstatustype_posts`*/;
SET @saved_cs_client     = @@character_set_client;

/*!50001 CREATE VIEW `wp_cb2_view_periodstatustype_posts` AS SELECT 
 1 AS `ID`,
 1 AS `post_author`,
 1 AS `post_date`,
 1 AS `post_date_gmt`,
 1 AS `post_content`,
 1 AS `post_title`,
 1 AS `post_excerpt`,
 1 AS `post_status`,
 1 AS `comment_status`,
 1 AS `ping_status`,
 1 AS `post_password`,
 1 AS `post_name`,
 1 AS `to_ping`,
 1 AS `pinged`,
 1 AS `post_modified`,
 1 AS `post_modified_gmt`,
 1 AS `post_content_filtered`,
 1 AS `post_parent`,
 1 AS `guid`,
 1 AS `menu_order`,
 1 AS `post_type`,
 1 AS `post_mime_type`,
 1 AS `comment_count`,
 1 AS `period_status_type_id`,
 1 AS `name`,
 1 AS `description`,
 1 AS `flags`,
 1 AS `colour`,
 1 AS `opacity`,
 1 AS `priority`,
 1 AS `system`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `wp_cb2_view_periodstatustypemeta`
--

DROP TABLE IF EXISTS `wp_cb2_view_periodstatustypemeta`;
/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_periodstatustypemeta`*/;
SET @saved_cs_client     = @@character_set_client;

/*!50001 CREATE VIEW `wp_cb2_view_periodstatustypemeta` AS SELECT 
 1 AS `meta_id`,
 1 AS `post_id`,
 1 AS `periodstatustype_id`,
 1 AS `meta_key`,
 1 AS `meta_value`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `wp_cb2_view_sequence_date`
--

DROP TABLE IF EXISTS `wp_cb2_view_sequence_date`;
/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_sequence_date`*/;
SET @saved_cs_client     = @@character_set_client;

/*!50001 CREATE VIEW `wp_cb2_view_sequence_date` AS SELECT 
 1 AS `num`,
 1 AS `datetime_start`,
 1 AS `datetime_end`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `wp_cb2_view_sequence_num`
--

DROP TABLE IF EXISTS `wp_cb2_view_sequence_num`;
/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_sequence_num`*/;
SET @saved_cs_client     = @@character_set_client;

/*!50001 CREATE VIEW `wp_cb2_view_sequence_num` AS SELECT 
 1 AS `num`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `wp_cb2_view_timeframe_options`
--

DROP TABLE IF EXISTS `wp_cb2_view_timeframe_options`;
/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_timeframe_options`*/;
SET @saved_cs_client     = @@character_set_client;

/*!50001 CREATE VIEW `wp_cb2_view_timeframe_options` AS SELECT 
 1 AS `timeframe_id`,
 1 AS `max-slots`,
 1 AS `closed-days-booking`,
 1 AS `consequtive-slots`,
 1 AS `use-codes`,
 1 AS `limit`,
 1 AS `holiday-provider`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `wp_commentmeta`
--

DROP TABLE IF EXISTS `wp_commentmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_commentmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext,
  PRIMARY KEY (`meta_id`),
  KEY `comment_id` (`comment_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_commentmeta`
--

LOCK TABLES `wp_commentmeta` WRITE;
/*!40000 ALTER TABLE `wp_commentmeta` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_commentmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_comments`
--

DROP TABLE IF EXISTS `wp_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_comments` (
  `comment_ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_post_ID` bigint(20) unsigned NOT NULL DEFAULT '0',
  `comment_author` tinytext NOT NULL,
  `comment_author_email` varchar(100) NOT NULL DEFAULT '',
  `comment_author_url` varchar(200) NOT NULL DEFAULT '',
  `comment_author_IP` varchar(100) NOT NULL DEFAULT '',
  `comment_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_content` text NOT NULL,
  `comment_karma` int(11) NOT NULL DEFAULT '0',
  `comment_approved` varchar(20) NOT NULL DEFAULT '1',
  `comment_agent` varchar(255) NOT NULL DEFAULT '',
  `comment_type` varchar(20) NOT NULL DEFAULT '',
  `comment_parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_ID`),
  KEY `comment_post_ID` (`comment_post_ID`),
  KEY `comment_approved_date_gmt` (`comment_approved`,`comment_date_gmt`),
  KEY `comment_date_gmt` (`comment_date_gmt`),
  KEY `comment_parent` (`comment_parent`),
  KEY `comment_author_email` (`comment_author_email`(10))
) ENGINE=InnoDB AUTO_INCREMENT=2;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_comments`
--

LOCK TABLES `wp_comments` WRITE;
/*!40000 ALTER TABLE `wp_comments` DISABLE KEYS */;
INSERT INTO `wp_comments` VALUES (1,1,'A WordPress Commenter','wapuu@wordpress.example','https://wordpress.org/','','2018-05-03 10:00:31','2018-05-03 10:00:31','Hi, this is a comment.\nTo get started with moderating, editing, and deleting comments, please visit the Comments screen in the dashboard.\nCommenter avatars come from <a href=\"https://gravatar.com\">Gravatar</a>.',0,'1','','',0,0);
/*!40000 ALTER TABLE `wp_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_links`
--

DROP TABLE IF EXISTS `wp_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_links` (
  `link_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `link_url` varchar(255) NOT NULL DEFAULT '',
  `link_name` varchar(255) NOT NULL DEFAULT '',
  `link_image` varchar(255) NOT NULL DEFAULT '',
  `link_target` varchar(25) NOT NULL DEFAULT '',
  `link_description` varchar(255) NOT NULL DEFAULT '',
  `link_visible` varchar(20) NOT NULL DEFAULT 'Y',
  `link_owner` bigint(20) unsigned NOT NULL DEFAULT '1',
  `link_rating` int(11) NOT NULL DEFAULT '0',
  `link_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `link_rel` varchar(255) NOT NULL DEFAULT '',
  `link_notes` mediumtext NOT NULL,
  `link_rss` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`link_id`),
  KEY `link_visible` (`link_visible`)
) ENGINE=InnoDB;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_links`
--

LOCK TABLES `wp_links` WRITE;
/*!40000 ALTER TABLE `wp_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_options`
--

DROP TABLE IF EXISTS `wp_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_options` (
  `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `option_name` varchar(191) NOT NULL DEFAULT '',
  `option_value` longtext NOT NULL,
  `autoload` varchar(20) NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`option_id`),
  UNIQUE KEY `option_name` (`option_name`)
) ENGINE=InnoDB AUTO_INCREMENT=1353;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_options`
--

LOCK TABLES `wp_options` WRITE;
/*!40000 ALTER TABLE `wp_options` DISABLE KEYS */;
INSERT INTO `wp_options` VALUES (1,'siteurl','http://commonsbooking.localhost','yes'),(2,'home','http://commonsbooking.localhost','yes'),(3,'blogname','Commons Booking','yes'),(4,'blogdescription','Just another WordPress site','yes'),(5,'users_can_register','0','yes'),(6,'admin_email','annesley_newholm@yahoo.it','yes'),(7,'start_of_week','1','yes'),(8,'use_balanceTags','0','yes'),(9,'use_smilies','1','yes'),(10,'require_name_email','1','yes'),(11,'comments_notify','1','yes'),(12,'posts_per_rss','10','yes'),(13,'rss_use_excerpt','0','yes'),(14,'mailserver_url','mail.example.com','yes'),(15,'mailserver_login','login@example.com','yes'),(16,'mailserver_pass','password','yes'),(17,'mailserver_port','110','yes'),(18,'default_category','1','yes'),(19,'default_comment_status','open','yes'),(20,'default_ping_status','open','yes'),(21,'default_pingback_flag','0','yes'),(22,'posts_per_page','10','yes'),(23,'date_format','F j, Y','yes'),(24,'time_format','g:i a','yes'),(25,'links_updated_date_format','F j, Y g:i a','yes'),(26,'comment_moderation','0','yes'),(27,'moderation_notify','1','yes'),(28,'permalink_structure','/%postname%/','yes'),(29,'rewrite_rules','a:430:{s:11:\"^wp-json/?$\";s:22:\"index.php?rest_route=/\";s:14:\"^wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:21:\"^index.php/wp-json/?$\";s:22:\"index.php?rest_route=/\";s:24:\"^index.php/wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:19:\"periodstatustype/?$\";s:36:\"index.php?post_type=periodstatustype\";s:49:\"periodstatustype/feed/(feed|rdf|rss|rss2|atom)/?$\";s:53:\"index.php?post_type=periodstatustype&feed=$matches[1]\";s:44:\"periodstatustype/(feed|rdf|rss|rss2|atom)/?$\";s:53:\"index.php?post_type=periodstatustype&feed=$matches[1]\";s:36:\"periodstatustype/page/([0-9]{1,})/?$\";s:54:\"index.php?post_type=periodstatustype&paged=$matches[1]\";s:9:\"period/?$\";s:26:\"index.php?post_type=period\";s:39:\"period/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?post_type=period&feed=$matches[1]\";s:34:\"period/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?post_type=period&feed=$matches[1]\";s:26:\"period/page/([0-9]{1,})/?$\";s:44:\"index.php?post_type=period&paged=$matches[1]\";s:23:\"perioditem-automatic/?$\";s:40:\"index.php?post_type=perioditem-automatic\";s:53:\"perioditem-automatic/feed/(feed|rdf|rss|rss2|atom)/?$\";s:57:\"index.php?post_type=perioditem-automatic&feed=$matches[1]\";s:48:\"perioditem-automatic/(feed|rdf|rss|rss2|atom)/?$\";s:57:\"index.php?post_type=perioditem-automatic&feed=$matches[1]\";s:40:\"perioditem-automatic/page/([0-9]{1,})/?$\";s:58:\"index.php?post_type=perioditem-automatic&paged=$matches[1]\";s:20:\"perioditem-global/?$\";s:37:\"index.php?post_type=perioditem-global\";s:50:\"perioditem-global/feed/(feed|rdf|rss|rss2|atom)/?$\";s:54:\"index.php?post_type=perioditem-global&feed=$matches[1]\";s:45:\"perioditem-global/(feed|rdf|rss|rss2|atom)/?$\";s:54:\"index.php?post_type=perioditem-global&feed=$matches[1]\";s:37:\"perioditem-global/page/([0-9]{1,})/?$\";s:55:\"index.php?post_type=perioditem-global&paged=$matches[1]\";s:22:\"perioditem-location/?$\";s:39:\"index.php?post_type=perioditem-location\";s:52:\"perioditem-location/feed/(feed|rdf|rss|rss2|atom)/?$\";s:56:\"index.php?post_type=perioditem-location&feed=$matches[1]\";s:47:\"perioditem-location/(feed|rdf|rss|rss2|atom)/?$\";s:56:\"index.php?post_type=perioditem-location&feed=$matches[1]\";s:39:\"perioditem-location/page/([0-9]{1,})/?$\";s:57:\"index.php?post_type=perioditem-location&paged=$matches[1]\";s:23:\"perioditem-timeframe/?$\";s:40:\"index.php?post_type=perioditem-timeframe\";s:53:\"perioditem-timeframe/feed/(feed|rdf|rss|rss2|atom)/?$\";s:57:\"index.php?post_type=perioditem-timeframe&feed=$matches[1]\";s:48:\"perioditem-timeframe/(feed|rdf|rss|rss2|atom)/?$\";s:57:\"index.php?post_type=perioditem-timeframe&feed=$matches[1]\";s:40:\"perioditem-timeframe/page/([0-9]{1,})/?$\";s:58:\"index.php?post_type=perioditem-timeframe&paged=$matches[1]\";s:18:\"perioditem-user/?$\";s:35:\"index.php?post_type=perioditem-user\";s:48:\"perioditem-user/feed/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?post_type=perioditem-user&feed=$matches[1]\";s:43:\"perioditem-user/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?post_type=perioditem-user&feed=$matches[1]\";s:35:\"perioditem-user/page/([0-9]{1,})/?$\";s:53:\"index.php?post_type=perioditem-user&paged=$matches[1]\";s:7:\"user/?$\";s:24:\"index.php?post_type=user\";s:37:\"user/feed/(feed|rdf|rss|rss2|atom)/?$\";s:41:\"index.php?post_type=user&feed=$matches[1]\";s:32:\"user/(feed|rdf|rss|rss2|atom)/?$\";s:41:\"index.php?post_type=user&feed=$matches[1]\";s:24:\"user/page/([0-9]{1,})/?$\";s:42:\"index.php?post_type=user&paged=$matches[1]\";s:11:\"location/?$\";s:28:\"index.php?post_type=location\";s:41:\"location/feed/(feed|rdf|rss|rss2|atom)/?$\";s:45:\"index.php?post_type=location&feed=$matches[1]\";s:36:\"location/(feed|rdf|rss|rss2|atom)/?$\";s:45:\"index.php?post_type=location&feed=$matches[1]\";s:28:\"location/page/([0-9]{1,})/?$\";s:46:\"index.php?post_type=location&paged=$matches[1]\";s:7:\"item/?$\";s:24:\"index.php?post_type=item\";s:37:\"item/feed/(feed|rdf|rss|rss2|atom)/?$\";s:41:\"index.php?post_type=item&feed=$matches[1]\";s:32:\"item/(feed|rdf|rss|rss2|atom)/?$\";s:41:\"index.php?post_type=item&feed=$matches[1]\";s:24:\"item/page/([0-9]{1,})/?$\";s:42:\"index.php?post_type=item&paged=$matches[1]\";s:7:\"year/?$\";s:24:\"index.php?post_type=year\";s:37:\"year/feed/(feed|rdf|rss|rss2|atom)/?$\";s:41:\"index.php?post_type=year&feed=$matches[1]\";s:32:\"year/(feed|rdf|rss|rss2|atom)/?$\";s:41:\"index.php?post_type=year&feed=$matches[1]\";s:24:\"year/page/([0-9]{1,})/?$\";s:42:\"index.php?post_type=year&paged=$matches[1]\";s:8:\"month/?$\";s:25:\"index.php?post_type=month\";s:38:\"month/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?post_type=month&feed=$matches[1]\";s:33:\"month/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?post_type=month&feed=$matches[1]\";s:25:\"month/page/([0-9]{1,})/?$\";s:43:\"index.php?post_type=month&paged=$matches[1]\";s:7:\"week/?$\";s:24:\"index.php?post_type=week\";s:37:\"week/feed/(feed|rdf|rss|rss2|atom)/?$\";s:41:\"index.php?post_type=week&feed=$matches[1]\";s:32:\"week/(feed|rdf|rss|rss2|atom)/?$\";s:41:\"index.php?post_type=week&feed=$matches[1]\";s:24:\"week/page/([0-9]{1,})/?$\";s:42:\"index.php?post_type=week&paged=$matches[1]\";s:6:\"day/?$\";s:23:\"index.php?post_type=day\";s:36:\"day/feed/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?post_type=day&feed=$matches[1]\";s:31:\"day/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?post_type=day&feed=$matches[1]\";s:23:\"day/page/([0-9]{1,})/?$\";s:41:\"index.php?post_type=day&paged=$matches[1]\";s:47:\"category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:42:\"category/(.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:23:\"category/(.+?)/embed/?$\";s:46:\"index.php?category_name=$matches[1]&embed=true\";s:35:\"category/(.+?)/page/?([0-9]{1,})/?$\";s:53:\"index.php?category_name=$matches[1]&paged=$matches[2]\";s:17:\"category/(.+?)/?$\";s:35:\"index.php?category_name=$matches[1]\";s:44:\"tag/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:39:\"tag/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:20:\"tag/([^/]+)/embed/?$\";s:36:\"index.php?tag=$matches[1]&embed=true\";s:32:\"tag/([^/]+)/page/?([0-9]{1,})/?$\";s:43:\"index.php?tag=$matches[1]&paged=$matches[2]\";s:14:\"tag/([^/]+)/?$\";s:25:\"index.php?tag=$matches[1]\";s:45:\"type/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:40:\"type/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:21:\"type/([^/]+)/embed/?$\";s:44:\"index.php?post_format=$matches[1]&embed=true\";s:33:\"type/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?post_format=$matches[1]&paged=$matches[2]\";s:15:\"type/([^/]+)/?$\";s:33:\"index.php?post_format=$matches[1]\";s:44:\"periodstatustype/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:54:\"periodstatustype/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:74:\"periodstatustype/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:69:\"periodstatustype/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:69:\"periodstatustype/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:50:\"periodstatustype/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:33:\"periodstatustype/([^/]+)/embed/?$\";s:49:\"index.php?periodstatustype=$matches[1]&embed=true\";s:37:\"periodstatustype/([^/]+)/trackback/?$\";s:43:\"index.php?periodstatustype=$matches[1]&tb=1\";s:57:\"periodstatustype/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:55:\"index.php?periodstatustype=$matches[1]&feed=$matches[2]\";s:52:\"periodstatustype/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:55:\"index.php?periodstatustype=$matches[1]&feed=$matches[2]\";s:45:\"periodstatustype/([^/]+)/page/?([0-9]{1,})/?$\";s:56:\"index.php?periodstatustype=$matches[1]&paged=$matches[2]\";s:52:\"periodstatustype/([^/]+)/comment-page-([0-9]{1,})/?$\";s:56:\"index.php?periodstatustype=$matches[1]&cpage=$matches[2]\";s:41:\"periodstatustype/([^/]+)(?:/([0-9]+))?/?$\";s:55:\"index.php?periodstatustype=$matches[1]&page=$matches[2]\";s:33:\"periodstatustype/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:43:\"periodstatustype/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:63:\"periodstatustype/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:58:\"periodstatustype/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:58:\"periodstatustype/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:39:\"periodstatustype/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:34:\"period/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:44:\"period/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:64:\"period/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:59:\"period/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:59:\"period/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:40:\"period/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:23:\"period/([^/]+)/embed/?$\";s:39:\"index.php?period=$matches[1]&embed=true\";s:27:\"period/([^/]+)/trackback/?$\";s:33:\"index.php?period=$matches[1]&tb=1\";s:47:\"period/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:45:\"index.php?period=$matches[1]&feed=$matches[2]\";s:42:\"period/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:45:\"index.php?period=$matches[1]&feed=$matches[2]\";s:35:\"period/([^/]+)/page/?([0-9]{1,})/?$\";s:46:\"index.php?period=$matches[1]&paged=$matches[2]\";s:42:\"period/([^/]+)/comment-page-([0-9]{1,})/?$\";s:46:\"index.php?period=$matches[1]&cpage=$matches[2]\";s:31:\"period/([^/]+)(?:/([0-9]+))?/?$\";s:45:\"index.php?period=$matches[1]&page=$matches[2]\";s:23:\"period/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:33:\"period/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:53:\"period/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:48:\"period/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:48:\"period/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:29:\"period/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:48:\"perioditem-automatic/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:58:\"perioditem-automatic/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:78:\"perioditem-automatic/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:73:\"perioditem-automatic/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:73:\"perioditem-automatic/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:54:\"perioditem-automatic/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:37:\"perioditem-automatic/([^/]+)/embed/?$\";s:53:\"index.php?perioditem-automatic=$matches[1]&embed=true\";s:41:\"perioditem-automatic/([^/]+)/trackback/?$\";s:47:\"index.php?perioditem-automatic=$matches[1]&tb=1\";s:61:\"perioditem-automatic/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:59:\"index.php?perioditem-automatic=$matches[1]&feed=$matches[2]\";s:56:\"perioditem-automatic/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:59:\"index.php?perioditem-automatic=$matches[1]&feed=$matches[2]\";s:49:\"perioditem-automatic/([^/]+)/page/?([0-9]{1,})/?$\";s:60:\"index.php?perioditem-automatic=$matches[1]&paged=$matches[2]\";s:56:\"perioditem-automatic/([^/]+)/comment-page-([0-9]{1,})/?$\";s:60:\"index.php?perioditem-automatic=$matches[1]&cpage=$matches[2]\";s:45:\"perioditem-automatic/([^/]+)(?:/([0-9]+))?/?$\";s:59:\"index.php?perioditem-automatic=$matches[1]&page=$matches[2]\";s:37:\"perioditem-automatic/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:47:\"perioditem-automatic/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:67:\"perioditem-automatic/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:62:\"perioditem-automatic/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:62:\"perioditem-automatic/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:43:\"perioditem-automatic/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:45:\"perioditem-global/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:55:\"perioditem-global/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:75:\"perioditem-global/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:70:\"perioditem-global/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:70:\"perioditem-global/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:51:\"perioditem-global/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:34:\"perioditem-global/([^/]+)/embed/?$\";s:50:\"index.php?perioditem-global=$matches[1]&embed=true\";s:38:\"perioditem-global/([^/]+)/trackback/?$\";s:44:\"index.php?perioditem-global=$matches[1]&tb=1\";s:58:\"perioditem-global/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:56:\"index.php?perioditem-global=$matches[1]&feed=$matches[2]\";s:53:\"perioditem-global/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:56:\"index.php?perioditem-global=$matches[1]&feed=$matches[2]\";s:46:\"perioditem-global/([^/]+)/page/?([0-9]{1,})/?$\";s:57:\"index.php?perioditem-global=$matches[1]&paged=$matches[2]\";s:53:\"perioditem-global/([^/]+)/comment-page-([0-9]{1,})/?$\";s:57:\"index.php?perioditem-global=$matches[1]&cpage=$matches[2]\";s:42:\"perioditem-global/([^/]+)(?:/([0-9]+))?/?$\";s:56:\"index.php?perioditem-global=$matches[1]&page=$matches[2]\";s:34:\"perioditem-global/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:44:\"perioditem-global/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:64:\"perioditem-global/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:59:\"perioditem-global/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:59:\"perioditem-global/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:40:\"perioditem-global/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:47:\"perioditem-location/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:57:\"perioditem-location/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:77:\"perioditem-location/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:72:\"perioditem-location/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:72:\"perioditem-location/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:53:\"perioditem-location/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:36:\"perioditem-location/([^/]+)/embed/?$\";s:52:\"index.php?perioditem-location=$matches[1]&embed=true\";s:40:\"perioditem-location/([^/]+)/trackback/?$\";s:46:\"index.php?perioditem-location=$matches[1]&tb=1\";s:60:\"perioditem-location/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:58:\"index.php?perioditem-location=$matches[1]&feed=$matches[2]\";s:55:\"perioditem-location/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:58:\"index.php?perioditem-location=$matches[1]&feed=$matches[2]\";s:48:\"perioditem-location/([^/]+)/page/?([0-9]{1,})/?$\";s:59:\"index.php?perioditem-location=$matches[1]&paged=$matches[2]\";s:55:\"perioditem-location/([^/]+)/comment-page-([0-9]{1,})/?$\";s:59:\"index.php?perioditem-location=$matches[1]&cpage=$matches[2]\";s:44:\"perioditem-location/([^/]+)(?:/([0-9]+))?/?$\";s:58:\"index.php?perioditem-location=$matches[1]&page=$matches[2]\";s:36:\"perioditem-location/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:46:\"perioditem-location/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:66:\"perioditem-location/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:61:\"perioditem-location/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:61:\"perioditem-location/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:42:\"perioditem-location/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:48:\"perioditem-timeframe/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:58:\"perioditem-timeframe/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:78:\"perioditem-timeframe/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:73:\"perioditem-timeframe/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:73:\"perioditem-timeframe/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:54:\"perioditem-timeframe/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:37:\"perioditem-timeframe/([^/]+)/embed/?$\";s:53:\"index.php?perioditem-timeframe=$matches[1]&embed=true\";s:41:\"perioditem-timeframe/([^/]+)/trackback/?$\";s:47:\"index.php?perioditem-timeframe=$matches[1]&tb=1\";s:61:\"perioditem-timeframe/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:59:\"index.php?perioditem-timeframe=$matches[1]&feed=$matches[2]\";s:56:\"perioditem-timeframe/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:59:\"index.php?perioditem-timeframe=$matches[1]&feed=$matches[2]\";s:49:\"perioditem-timeframe/([^/]+)/page/?([0-9]{1,})/?$\";s:60:\"index.php?perioditem-timeframe=$matches[1]&paged=$matches[2]\";s:56:\"perioditem-timeframe/([^/]+)/comment-page-([0-9]{1,})/?$\";s:60:\"index.php?perioditem-timeframe=$matches[1]&cpage=$matches[2]\";s:45:\"perioditem-timeframe/([^/]+)(?:/([0-9]+))?/?$\";s:59:\"index.php?perioditem-timeframe=$matches[1]&page=$matches[2]\";s:37:\"perioditem-timeframe/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:47:\"perioditem-timeframe/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:67:\"perioditem-timeframe/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:62:\"perioditem-timeframe/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:62:\"perioditem-timeframe/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:43:\"perioditem-timeframe/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:43:\"perioditem-user/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:53:\"perioditem-user/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:73:\"perioditem-user/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:68:\"perioditem-user/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:68:\"perioditem-user/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:49:\"perioditem-user/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:32:\"perioditem-user/([^/]+)/embed/?$\";s:48:\"index.php?perioditem-user=$matches[1]&embed=true\";s:36:\"perioditem-user/([^/]+)/trackback/?$\";s:42:\"index.php?perioditem-user=$matches[1]&tb=1\";s:56:\"perioditem-user/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:54:\"index.php?perioditem-user=$matches[1]&feed=$matches[2]\";s:51:\"perioditem-user/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:54:\"index.php?perioditem-user=$matches[1]&feed=$matches[2]\";s:44:\"perioditem-user/([^/]+)/page/?([0-9]{1,})/?$\";s:55:\"index.php?perioditem-user=$matches[1]&paged=$matches[2]\";s:51:\"perioditem-user/([^/]+)/comment-page-([0-9]{1,})/?$\";s:55:\"index.php?perioditem-user=$matches[1]&cpage=$matches[2]\";s:40:\"perioditem-user/([^/]+)(?:/([0-9]+))?/?$\";s:54:\"index.php?perioditem-user=$matches[1]&page=$matches[2]\";s:32:\"perioditem-user/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:42:\"perioditem-user/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:62:\"perioditem-user/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:57:\"perioditem-user/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:57:\"perioditem-user/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:38:\"perioditem-user/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:32:\"user/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:42:\"user/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:62:\"user/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:57:\"user/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:57:\"user/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:38:\"user/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:21:\"user/([^/]+)/embed/?$\";s:37:\"index.php?user=$matches[1]&embed=true\";s:25:\"user/([^/]+)/trackback/?$\";s:31:\"index.php?user=$matches[1]&tb=1\";s:45:\"user/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?user=$matches[1]&feed=$matches[2]\";s:40:\"user/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?user=$matches[1]&feed=$matches[2]\";s:33:\"user/([^/]+)/page/?([0-9]{1,})/?$\";s:44:\"index.php?user=$matches[1]&paged=$matches[2]\";s:40:\"user/([^/]+)/comment-page-([0-9]{1,})/?$\";s:44:\"index.php?user=$matches[1]&cpage=$matches[2]\";s:29:\"user/([^/]+)(?:/([0-9]+))?/?$\";s:43:\"index.php?user=$matches[1]&page=$matches[2]\";s:21:\"user/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:31:\"user/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:51:\"user/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:46:\"user/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:46:\"user/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:27:\"user/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:36:\"location/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:46:\"location/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:66:\"location/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:61:\"location/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:61:\"location/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:42:\"location/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:25:\"location/([^/]+)/embed/?$\";s:41:\"index.php?location=$matches[1]&embed=true\";s:29:\"location/([^/]+)/trackback/?$\";s:35:\"index.php?location=$matches[1]&tb=1\";s:49:\"location/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?location=$matches[1]&feed=$matches[2]\";s:44:\"location/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?location=$matches[1]&feed=$matches[2]\";s:37:\"location/([^/]+)/page/?([0-9]{1,})/?$\";s:48:\"index.php?location=$matches[1]&paged=$matches[2]\";s:44:\"location/([^/]+)/comment-page-([0-9]{1,})/?$\";s:48:\"index.php?location=$matches[1]&cpage=$matches[2]\";s:33:\"location/([^/]+)(?:/([0-9]+))?/?$\";s:47:\"index.php?location=$matches[1]&page=$matches[2]\";s:25:\"location/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:35:\"location/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:55:\"location/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:50:\"location/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:50:\"location/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:31:\"location/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:32:\"item/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:42:\"item/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:62:\"item/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:57:\"item/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:57:\"item/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:38:\"item/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:21:\"item/([^/]+)/embed/?$\";s:37:\"index.php?item=$matches[1]&embed=true\";s:25:\"item/([^/]+)/trackback/?$\";s:31:\"index.php?item=$matches[1]&tb=1\";s:45:\"item/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?item=$matches[1]&feed=$matches[2]\";s:40:\"item/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?item=$matches[1]&feed=$matches[2]\";s:33:\"item/([^/]+)/page/?([0-9]{1,})/?$\";s:44:\"index.php?item=$matches[1]&paged=$matches[2]\";s:40:\"item/([^/]+)/comment-page-([0-9]{1,})/?$\";s:44:\"index.php?item=$matches[1]&cpage=$matches[2]\";s:29:\"item/([^/]+)(?:/([0-9]+))?/?$\";s:43:\"index.php?item=$matches[1]&page=$matches[2]\";s:21:\"item/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:31:\"item/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:51:\"item/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:46:\"item/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:46:\"item/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:27:\"item/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:32:\"year/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:42:\"year/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:62:\"year/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:57:\"year/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:57:\"year/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:38:\"year/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:21:\"year/([^/]+)/embed/?$\";s:37:\"index.php?year=$matches[1]&embed=true\";s:25:\"year/([^/]+)/trackback/?$\";s:31:\"index.php?year=$matches[1]&tb=1\";s:45:\"year/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:40:\"year/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:33:\"year/([^/]+)/page/?([0-9]{1,})/?$\";s:44:\"index.php?year=$matches[1]&paged=$matches[2]\";s:40:\"year/([^/]+)/comment-page-([0-9]{1,})/?$\";s:44:\"index.php?year=$matches[1]&cpage=$matches[2]\";s:29:\"year/([^/]+)(?:/([0-9]+))?/?$\";s:43:\"index.php?year=$matches[1]&page=$matches[2]\";s:21:\"year/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:31:\"year/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:51:\"year/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:46:\"year/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:46:\"year/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:27:\"year/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:33:\"month/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:43:\"month/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:63:\"month/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:58:\"month/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:58:\"month/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:39:\"month/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:22:\"month/([^/]+)/embed/?$\";s:38:\"index.php?month=$matches[1]&embed=true\";s:26:\"month/([^/]+)/trackback/?$\";s:32:\"index.php?month=$matches[1]&tb=1\";s:46:\"month/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:44:\"index.php?month=$matches[1]&feed=$matches[2]\";s:41:\"month/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:44:\"index.php?month=$matches[1]&feed=$matches[2]\";s:34:\"month/([^/]+)/page/?([0-9]{1,})/?$\";s:45:\"index.php?month=$matches[1]&paged=$matches[2]\";s:41:\"month/([^/]+)/comment-page-([0-9]{1,})/?$\";s:45:\"index.php?month=$matches[1]&cpage=$matches[2]\";s:30:\"month/([^/]+)(?:/([0-9]+))?/?$\";s:44:\"index.php?month=$matches[1]&page=$matches[2]\";s:22:\"month/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:32:\"month/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:52:\"month/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:47:\"month/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:47:\"month/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:28:\"month/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:32:\"week/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:42:\"week/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:62:\"week/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:57:\"week/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:57:\"week/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:38:\"week/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:21:\"week/([^/]+)/embed/?$\";s:37:\"index.php?week=$matches[1]&embed=true\";s:25:\"week/([^/]+)/trackback/?$\";s:31:\"index.php?week=$matches[1]&tb=1\";s:45:\"week/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?week=$matches[1]&feed=$matches[2]\";s:40:\"week/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?week=$matches[1]&feed=$matches[2]\";s:33:\"week/([^/]+)/page/?([0-9]{1,})/?$\";s:44:\"index.php?week=$matches[1]&paged=$matches[2]\";s:40:\"week/([^/]+)/comment-page-([0-9]{1,})/?$\";s:44:\"index.php?week=$matches[1]&cpage=$matches[2]\";s:29:\"week/([^/]+)(?:/([0-9]+))?/?$\";s:43:\"index.php?week=$matches[1]&page=$matches[2]\";s:21:\"week/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:31:\"week/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:51:\"week/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:46:\"week/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:46:\"week/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:27:\"week/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:31:\"day/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:41:\"day/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:61:\"day/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:56:\"day/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:56:\"day/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:37:\"day/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:20:\"day/([^/]+)/embed/?$\";s:36:\"index.php?day=$matches[1]&embed=true\";s:24:\"day/([^/]+)/trackback/?$\";s:30:\"index.php?day=$matches[1]&tb=1\";s:44:\"day/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?day=$matches[1]&feed=$matches[2]\";s:39:\"day/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?day=$matches[1]&feed=$matches[2]\";s:32:\"day/([^/]+)/page/?([0-9]{1,})/?$\";s:43:\"index.php?day=$matches[1]&paged=$matches[2]\";s:39:\"day/([^/]+)/comment-page-([0-9]{1,})/?$\";s:43:\"index.php?day=$matches[1]&cpage=$matches[2]\";s:28:\"day/([^/]+)(?:/([0-9]+))?/?$\";s:42:\"index.php?day=$matches[1]&page=$matches[2]\";s:20:\"day/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:30:\"day/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:50:\"day/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:45:\"day/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:45:\"day/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:26:\"day/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:12:\"robots\\.txt$\";s:18:\"index.php?robots=1\";s:48:\".*wp-(atom|rdf|rss|rss2|feed|commentsrss2)\\.php$\";s:18:\"index.php?feed=old\";s:20:\".*wp-app\\.php(/.*)?$\";s:19:\"index.php?error=403\";s:18:\".*wp-register.php$\";s:23:\"index.php?register=true\";s:32:\"feed/(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:27:\"(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:8:\"embed/?$\";s:21:\"index.php?&embed=true\";s:20:\"page/?([0-9]{1,})/?$\";s:28:\"index.php?&paged=$matches[1]\";s:41:\"comments/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:36:\"comments/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:17:\"comments/embed/?$\";s:21:\"index.php?&embed=true\";s:44:\"search/(.+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:39:\"search/(.+)/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:20:\"search/(.+)/embed/?$\";s:34:\"index.php?s=$matches[1]&embed=true\";s:32:\"search/(.+)/page/?([0-9]{1,})/?$\";s:41:\"index.php?s=$matches[1]&paged=$matches[2]\";s:14:\"search/(.+)/?$\";s:23:\"index.php?s=$matches[1]\";s:47:\"author/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:42:\"author/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:23:\"author/([^/]+)/embed/?$\";s:44:\"index.php?author_name=$matches[1]&embed=true\";s:35:\"author/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?author_name=$matches[1]&paged=$matches[2]\";s:17:\"author/([^/]+)/?$\";s:33:\"index.php?author_name=$matches[1]\";s:44:\"[^/]+/[0-9]{1,2}/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:54:\"[^/]+/[0-9]{1,2}/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:74:\"[^/]+/[0-9]{1,2}/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:69:\"[^/]+/[0-9]{1,2}/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:69:\"[^/]+/[0-9]{1,2}/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:50:\"[^/]+/[0-9]{1,2}/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:37:\"([^/]+)/([0-9]{1,2})/([^/]+)/embed/?$\";s:74:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&embed=true\";s:41:\"([^/]+)/([0-9]{1,2})/([^/]+)/trackback/?$\";s:68:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&tb=1\";s:61:\"([^/]+)/([0-9]{1,2})/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:56:\"([^/]+)/([0-9]{1,2})/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:49:\"([^/]+)/([0-9]{1,2})/([^/]+)/page/?([0-9]{1,})/?$\";s:81:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]\";s:45:\"([^/]+)/([0-9]{1,2})/([^/]+)(?:/([0-9]+))?/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&page=$matches[4]\";s:33:\"[^/]+/[0-9]{1,2}/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:43:\"[^/]+/[0-9]{1,2}/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:63:\"[^/]+/[0-9]{1,2}/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:58:\"[^/]+/[0-9]{1,2}/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:58:\"[^/]+/[0-9]{1,2}/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:39:\"[^/]+/[0-9]{1,2}/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:38:\"[^/]+/[0-9]{1,2}/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:48:\"[^/]+/[0-9]{1,2}/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:68:\"[^/]+/[0-9]{1,2}/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:63:\"[^/]+/[0-9]{1,2}/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:63:\"[^/]+/[0-9]{1,2}/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:44:\"[^/]+/[0-9]{1,2}/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:29:\"([^/]+)/([0-9]{1,2})/embed/?$\";s:58:\"index.php?year=$matches[1]&monthnum=$matches[2]&embed=true\";s:33:\"([^/]+)/([0-9]{1,2})/trackback/?$\";s:52:\"index.php?year=$matches[1]&monthnum=$matches[2]&tb=1\";s:53:\"([^/]+)/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:48:\"([^/]+)/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:41:\"([^/]+)/([0-9]{1,2})/page/?([0-9]{1,})/?$\";s:65:\"index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]\";s:37:\"([^/]+)/([0-9]{1,2})(?:/([0-9]+))?/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&page=$matches[3]\";s:27:\"[^/]+/[0-9]{1,2}/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:37:\"[^/]+/[0-9]{1,2}/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:57:\"[^/]+/[0-9]{1,2}/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\"[^/]+/[0-9]{1,2}/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\"[^/]+/[0-9]{1,2}/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:33:\"[^/]+/[0-9]{1,2}/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:27:\"[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:37:\"[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:57:\"[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\"[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\"[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:33:\"[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:16:\"([^/]+)/embed/?$\";s:37:\"index.php?name=$matches[1]&embed=true\";s:20:\"([^/]+)/trackback/?$\";s:31:\"index.php?name=$matches[1]&tb=1\";s:40:\"([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?name=$matches[1]&feed=$matches[2]\";s:35:\"([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?name=$matches[1]&feed=$matches[2]\";s:28:\"([^/]+)/page/?([0-9]{1,})/?$\";s:44:\"index.php?name=$matches[1]&paged=$matches[2]\";s:24:\"([^/]+)(?:/([0-9]+))?/?$\";s:43:\"index.php?name=$matches[1]&page=$matches[2]\";s:16:\"[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:26:\"[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:46:\"[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:41:\"[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:41:\"[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:22:\"[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:27:\".?.+?/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:37:\".?.+?/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:57:\".?.+?/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:33:\".?.+?/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:16:\"(.?.+?)/embed/?$\";s:41:\"index.php?pagename=$matches[1]&embed=true\";s:20:\"(.?.+?)/trackback/?$\";s:35:\"index.php?pagename=$matches[1]&tb=1\";s:40:\"(.?.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:35:\"(.?.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:28:\"(.?.+?)/page/?([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&paged=$matches[2]\";s:35:\"(.?.+?)/comment-page-([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&cpage=$matches[2]\";s:24:\"(.?.+?)(?:/([0-9]+))?/?$\";s:47:\"index.php?pagename=$matches[1]&page=$matches[2]\";s:35:\"([^/]+)/comment-page-([0-9]{1,})/?$\";s:44:\"index.php?name=$matches[1]&cpage=$matches[2]\";}','yes'),(30,'hack_file','0','yes'),(31,'blog_charset','UTF-8','yes'),(32,'moderation_keys','','no'),(33,'active_plugins','a:2:{i:0;s:31:\"query-monitor/query-monitor.php\";i:1;s:37:\"commons-booking-2/commons-booking.php\";}','yes'),(34,'category_base','','yes'),(35,'ping_sites','http://rpc.pingomatic.com/','yes'),(36,'comment_max_links','2','yes'),(37,'gmt_offset','0','yes'),(38,'default_email_category','1','yes'),(39,'recently_edited','','no'),(40,'template','twentyseventeen','yes'),(41,'stylesheet','twentyseventeen','yes'),(42,'comment_whitelist','1','yes'),(43,'blacklist_keys','','no'),(44,'comment_registration','0','yes'),(45,'html_type','text/html','yes'),(46,'use_trackback','0','yes'),(47,'default_role','subscriber','yes'),(48,'db_version','38590','yes'),(49,'uploads_use_yearmonth_folders','1','yes'),(50,'upload_path','','yes'),(51,'blog_public','0','yes'),(52,'default_link_category','2','yes'),(53,'show_on_front','posts','yes'),(54,'tag_base','','yes'),(55,'show_avatars','1','yes'),(56,'avatar_rating','G','yes'),(57,'upload_url_path','','yes'),(58,'thumbnail_size_w','150','yes'),(59,'thumbnail_size_h','150','yes'),(60,'thumbnail_crop','1','yes'),(61,'medium_size_w','300','yes'),(62,'medium_size_h','300','yes'),(63,'avatar_default','mystery','yes'),(64,'large_size_w','1024','yes'),(65,'large_size_h','1024','yes'),(66,'image_default_link_type','none','yes'),(67,'image_default_size','','yes'),(68,'image_default_align','','yes'),(69,'close_comments_for_old_posts','0','yes'),(70,'close_comments_days_old','14','yes'),(71,'thread_comments','1','yes'),(72,'thread_comments_depth','5','yes'),(73,'page_comments','0','yes'),(74,'comments_per_page','50','yes'),(75,'default_comments_page','newest','yes'),(76,'comment_order','asc','yes'),(77,'sticky_posts','a:0:{}','yes'),(78,'widget_categories','a:2:{i:2;a:4:{s:5:\"title\";s:0:\"\";s:5:\"count\";i:0;s:12:\"hierarchical\";i:0;s:8:\"dropdown\";i:0;}s:12:\"_multiwidget\";i:1;}','yes'),(79,'widget_text','a:0:{}','yes'),(80,'widget_rss','a:0:{}','yes'),(81,'uninstall_plugins','a:0:{}','no'),(82,'timezone_string','','yes'),(83,'page_for_posts','0','yes'),(84,'page_on_front','0','yes'),(85,'default_post_format','0','yes'),(86,'link_manager_enabled','0','yes'),(87,'finished_splitting_shared_terms','1','yes'),(88,'site_icon','0','yes'),(89,'medium_large_size_w','768','yes'),(90,'medium_large_size_h','0','yes'),(91,'initial_db_version','38590','yes'),(92,'wp_user_roles','a:7:{s:13:\"administrator\";a:2:{s:4:\"name\";s:13:\"Administrator\";s:12:\"capabilities\";a:67:{s:13:\"switch_themes\";b:1;s:11:\"edit_themes\";b:1;s:16:\"activate_plugins\";b:1;s:12:\"edit_plugins\";b:1;s:10:\"edit_users\";b:1;s:10:\"edit_files\";b:1;s:14:\"manage_options\";b:1;s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:6:\"import\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:8:\"level_10\";b:1;s:7:\"level_9\";b:1;s:7:\"level_8\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;s:12:\"delete_users\";b:1;s:12:\"create_users\";b:1;s:17:\"unfiltered_upload\";b:1;s:14:\"edit_dashboard\";b:1;s:14:\"update_plugins\";b:1;s:14:\"delete_plugins\";b:1;s:15:\"install_plugins\";b:1;s:13:\"update_themes\";b:1;s:14:\"install_themes\";b:1;s:11:\"update_core\";b:1;s:10:\"list_users\";b:1;s:12:\"remove_users\";b:1;s:13:\"promote_users\";b:1;s:18:\"edit_theme_options\";b:1;s:13:\"delete_themes\";b:1;s:6:\"export\";b:1;s:33:\"edit_published_answerpacktv_leads\";b:1;s:23:\"edit_answerpacktv_leads\";b:1;s:30:\"edit_others_answerpacktv_leads\";b:1;s:21:\"edit_published_videos\";b:1;s:11:\"edit_videos\";b:1;s:18:\"edit_others_videos\";b:1;}}s:6:\"editor\";a:2:{s:4:\"name\";s:6:\"Editor\";s:12:\"capabilities\";a:38:{s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;s:33:\"edit_published_answerpacktv_leads\";b:1;s:23:\"edit_answerpacktv_leads\";b:1;s:21:\"edit_published_videos\";b:1;s:11:\"edit_videos\";b:1;}}s:6:\"author\";a:2:{s:4:\"name\";s:6:\"Author\";s:12:\"capabilities\";a:14:{s:12:\"upload_files\";b:1;s:10:\"edit_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:33:\"edit_published_answerpacktv_leads\";b:1;s:23:\"edit_answerpacktv_leads\";b:1;s:21:\"edit_published_videos\";b:1;s:11:\"edit_videos\";b:1;}}s:11:\"contributor\";a:2:{s:4:\"name\";s:11:\"Contributor\";s:12:\"capabilities\";a:5:{s:10:\"edit_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;}}s:10:\"subscriber\";a:2:{s:4:\"name\";s:10:\"Subscriber\";s:12:\"capabilities\";a:6:{s:4:\"read\";b:1;s:7:\"level_0\";b:1;s:33:\"edit_published_answerpacktv_leads\";b:1;s:23:\"edit_answerpacktv_leads\";b:1;s:21:\"edit_published_videos\";b:1;s:11:\"edit_videos\";b:1;}}s:25:\"answerpacktv_lead_manager\";a:2:{s:4:\"name\";s:25:\"answerpacktv_lead_manager\";s:12:\"capabilities\";a:3:{s:33:\"edit_published_answerpacktv_leads\";b:1;s:23:\"edit_answerpacktv_leads\";b:1;s:4:\"read\";b:1;}}s:17:\"video_contributor\";a:2:{s:4:\"name\";s:17:\"video_contributor\";s:12:\"capabilities\";a:3:{s:21:\"edit_published_videos\";b:1;s:11:\"edit_videos\";b:1;s:4:\"read\";b:1;}}}','yes'),(93,'fresh_site','0','yes'),(94,'widget_search','a:2:{i:2;a:1:{s:5:\"title\";s:0:\"\";}s:12:\"_multiwidget\";i:1;}','yes'),(95,'widget_recent-posts','a:2:{i:2;a:2:{s:5:\"title\";s:0:\"\";s:6:\"number\";i:5;}s:12:\"_multiwidget\";i:1;}','yes'),(96,'widget_recent-comments','a:2:{i:2;a:2:{s:5:\"title\";s:0:\"\";s:6:\"number\";i:5;}s:12:\"_multiwidget\";i:1;}','yes'),(97,'widget_archives','a:2:{i:2;a:3:{s:5:\"title\";s:0:\"\";s:5:\"count\";i:0;s:8:\"dropdown\";i:0;}s:12:\"_multiwidget\";i:1;}','yes'),(98,'widget_meta','a:2:{i:2;a:1:{s:5:\"title\";s:0:\"\";}s:12:\"_multiwidget\";i:1;}','yes'),(99,'sidebars_widgets','a:5:{s:19:\"wp_inactive_widgets\";a:0:{}s:9:\"sidebar-1\";a:6:{i:0;s:8:\"search-2\";i:1;s:14:\"recent-posts-2\";i:2;s:17:\"recent-comments-2\";i:3;s:10:\"archives-2\";i:4;s:12:\"categories-2\";i:5;s:6:\"meta-2\";}s:9:\"sidebar-2\";a:0:{}s:9:\"sidebar-3\";a:0:{}s:13:\"array_version\";i:3;}','yes'),(100,'widget_pages','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(101,'widget_calendar','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(102,'widget_media_audio','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(103,'widget_media_image','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(104,'widget_media_gallery','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(105,'widget_media_video','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(106,'widget_tag_cloud','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(107,'widget_nav_menu','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(108,'widget_custom_html','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(109,'cron','a:5:{i:1536052302;a:1:{s:34:\"wp_privacy_delete_old_export_files\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}}i:1536055233;a:3:{s:16:\"wp_version_check\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:17:\"wp_update_plugins\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:16:\"wp_update_themes\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1536069188;a:2:{s:19:\"wp_scheduled_delete\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:25:\"delete_expired_transients\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1536070341;a:1:{s:30:\"wp_scheduled_auto_draft_delete\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}s:7:\"version\";i:2;}','yes'),(110,'theme_mods_twentyseventeen','a:1:{s:18:\"custom_css_post_id\";i:-1;}','yes'),(114,'_site_transient_update_core','O:8:\"stdClass\":4:{s:7:\"updates\";a:2:{i:0;O:8:\"stdClass\":10:{s:8:\"response\";s:7:\"upgrade\";s:8:\"download\";s:59:\"https://downloads.wordpress.org/release/wordpress-4.9.8.zip\";s:6:\"locale\";s:5:\"en_US\";s:8:\"packages\";O:8:\"stdClass\":5:{s:4:\"full\";s:59:\"https://downloads.wordpress.org/release/wordpress-4.9.8.zip\";s:10:\"no_content\";s:70:\"https://downloads.wordpress.org/release/wordpress-4.9.8-no-content.zip\";s:11:\"new_bundled\";s:71:\"https://downloads.wordpress.org/release/wordpress-4.9.8-new-bundled.zip\";s:7:\"partial\";s:69:\"https://downloads.wordpress.org/release/wordpress-4.9.8-partial-7.zip\";s:8:\"rollback\";b:0;}s:7:\"current\";s:5:\"4.9.8\";s:7:\"version\";s:5:\"4.9.8\";s:11:\"php_version\";s:5:\"5.2.4\";s:13:\"mysql_version\";s:3:\"5.0\";s:11:\"new_bundled\";s:3:\"4.7\";s:15:\"partial_version\";s:5:\"4.9.7\";}i:1;O:8:\"stdClass\":11:{s:8:\"response\";s:10:\"autoupdate\";s:8:\"download\";s:59:\"https://downloads.wordpress.org/release/wordpress-4.9.8.zip\";s:6:\"locale\";s:5:\"en_US\";s:8:\"packages\";O:8:\"stdClass\":5:{s:4:\"full\";s:59:\"https://downloads.wordpress.org/release/wordpress-4.9.8.zip\";s:10:\"no_content\";s:70:\"https://downloads.wordpress.org/release/wordpress-4.9.8-no-content.zip\";s:11:\"new_bundled\";s:71:\"https://downloads.wordpress.org/release/wordpress-4.9.8-new-bundled.zip\";s:7:\"partial\";s:69:\"https://downloads.wordpress.org/release/wordpress-4.9.8-partial-7.zip\";s:8:\"rollback\";s:70:\"https://downloads.wordpress.org/release/wordpress-4.9.8-rollback-7.zip\";}s:7:\"current\";s:5:\"4.9.8\";s:7:\"version\";s:5:\"4.9.8\";s:11:\"php_version\";s:5:\"5.2.4\";s:13:\"mysql_version\";s:3:\"5.0\";s:11:\"new_bundled\";s:3:\"4.7\";s:15:\"partial_version\";s:5:\"4.9.7\";s:9:\"new_files\";s:0:\"\";}}s:12:\"last_checked\";i:1536050731;s:15:\"version_checked\";s:5:\"4.9.7\";s:12:\"translations\";a:0:{}}','no'),(122,'can_compress_scripts','0','no'),(149,'recently_activated','a:0:{}','yes'),(161,'answerpacktv_show_activation_message','1','yes'),(162,'answerpacktv_settings','a:1:{s:17:\"subtitles_default\";b:1;}','yes'),(757,'qw_settings','a:5:{s:10:\"edit_theme\";s:5:\"views\";s:12:\"live_preview\";i:0;s:16:\"show_silent_meta\";i:0;s:24:\"meta_value_field_handler\";i:0;s:19:\"widget_theme_compat\";i:0;}','yes'),(758,'templatewrangler_version','2.1','yes'),(766,'qw_live_preview','on','yes'),(767,'qw_edit_theme','views','yes'),(768,'qw_plugin_version','1.543','yes'),(783,'wpcf7','a:1:{s:7:\"version\";s:5:\"5.0.3\";}','yes'),(784,'widget_query-wrangler-widget','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(1262,'_site_transient_timeout_browser_5e807558ddeaee8b37f93631f6842eab','1536304263','no'),(1263,'_site_transient_browser_5e807558ddeaee8b37f93631f6842eab','a:10:{s:4:\"name\";s:6:\"Chrome\";s:7:\"version\";s:12:\"67.0.3396.99\";s:8:\"platform\";s:5:\"Linux\";s:10:\"update_url\";s:29:\"https://www.google.com/chrome\";s:7:\"img_src\";s:43:\"http://s.w.org/images/browsers/chrome.png?1\";s:11:\"img_src_ssl\";s:44:\"https://s.w.org/images/browsers/chrome.png?1\";s:15:\"current_version\";s:2:\"18\";s:7:\"upgrade\";b:0;s:8:\"insecure\";b:0;s:6:\"mobile\";b:0;}','no'),(1349,'_site_transient_timeout_theme_roots','1536052532','no'),(1350,'_site_transient_theme_roots','a:3:{s:13:\"twentyfifteen\";s:7:\"/themes\";s:15:\"twentyseventeen\";s:7:\"/themes\";s:13:\"twentysixteen\";s:7:\"/themes\";}','no'),(1351,'_site_transient_update_themes','O:8:\"stdClass\":4:{s:12:\"last_checked\";i:1536050734;s:7:\"checked\";a:3:{s:13:\"twentyfifteen\";s:3:\"2.0\";s:15:\"twentyseventeen\";s:3:\"1.6\";s:13:\"twentysixteen\";s:3:\"1.5\";}s:8:\"response\";a:1:{s:15:\"twentyseventeen\";a:4:{s:5:\"theme\";s:15:\"twentyseventeen\";s:11:\"new_version\";s:3:\"1.7\";s:3:\"url\";s:45:\"https://wordpress.org/themes/twentyseventeen/\";s:7:\"package\";s:61:\"https://downloads.wordpress.org/theme/twentyseventeen.1.7.zip\";}}s:12:\"translations\";a:0:{}}','no'),(1352,'_site_transient_update_plugins','O:8:\"stdClass\":5:{s:12:\"last_checked\";i:1536050735;s:7:\"checked\";a:5:{s:19:\"akismet/akismet.php\";s:5:\"4.0.3\";s:29:\"answerpacktv/answerpacktv.php\";s:3:\"1.0\";s:37:\"commons-booking-2/commons-booking.php\";s:5:\"2.0.0\";s:9:\"hello.php\";s:3:\"1.7\";s:31:\"query-monitor/query-monitor.php\";s:5:\"3.1.0\";}s:8:\"response\";a:1:{s:19:\"akismet/akismet.php\";O:8:\"stdClass\":12:{s:2:\"id\";s:21:\"w.org/plugins/akismet\";s:4:\"slug\";s:7:\"akismet\";s:6:\"plugin\";s:19:\"akismet/akismet.php\";s:11:\"new_version\";s:5:\"4.0.8\";s:3:\"url\";s:38:\"https://wordpress.org/plugins/akismet/\";s:7:\"package\";s:56:\"https://downloads.wordpress.org/plugin/akismet.4.0.8.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:59:\"https://ps.w.org/akismet/assets/icon-256x256.png?rev=969272\";s:2:\"1x\";s:59:\"https://ps.w.org/akismet/assets/icon-128x128.png?rev=969272\";}s:7:\"banners\";a:1:{s:2:\"1x\";s:61:\"https://ps.w.org/akismet/assets/banner-772x250.jpg?rev=479904\";}s:11:\"banners_rtl\";a:0:{}s:6:\"tested\";s:5:\"4.9.8\";s:12:\"requires_php\";b:0;s:13:\"compatibility\";O:8:\"stdClass\":0:{}}}s:12:\"translations\";a:0:{}s:9:\"no_update\";a:3:{s:37:\"commons-booking-2/commons-booking.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:29:\"w.org/plugins/commons-booking\";s:4:\"slug\";s:15:\"commons-booking\";s:6:\"plugin\";s:37:\"commons-booking-2/commons-booking.php\";s:11:\"new_version\";s:8:\"0.9.2.12\";s:3:\"url\";s:46:\"https://wordpress.org/plugins/commons-booking/\";s:7:\"package\";s:67:\"https://downloads.wordpress.org/plugin/commons-booking.0.9.2.12.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:68:\"https://ps.w.org/commons-booking/assets/icon-256x256.png?rev=1642507\";s:2:\"1x\";s:68:\"https://ps.w.org/commons-booking/assets/icon-128x128.png?rev=1642507\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:71:\"https://ps.w.org/commons-booking/assets/banner-1544x500.png?rev=1642507\";s:2:\"1x\";s:70:\"https://ps.w.org/commons-booking/assets/banner-772x250.png?rev=1642507\";}s:11:\"banners_rtl\";a:0:{}}s:9:\"hello.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:25:\"w.org/plugins/hello-dolly\";s:4:\"slug\";s:11:\"hello-dolly\";s:6:\"plugin\";s:9:\"hello.php\";s:11:\"new_version\";s:3:\"1.6\";s:3:\"url\";s:42:\"https://wordpress.org/plugins/hello-dolly/\";s:7:\"package\";s:58:\"https://downloads.wordpress.org/plugin/hello-dolly.1.6.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:63:\"https://ps.w.org/hello-dolly/assets/icon-256x256.jpg?rev=969907\";s:2:\"1x\";s:63:\"https://ps.w.org/hello-dolly/assets/icon-128x128.jpg?rev=969907\";}s:7:\"banners\";a:1:{s:2:\"1x\";s:65:\"https://ps.w.org/hello-dolly/assets/banner-772x250.png?rev=478342\";}s:11:\"banners_rtl\";a:0:{}}s:31:\"query-monitor/query-monitor.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:27:\"w.org/plugins/query-monitor\";s:4:\"slug\";s:13:\"query-monitor\";s:6:\"plugin\";s:31:\"query-monitor/query-monitor.php\";s:11:\"new_version\";s:5:\"3.1.0\";s:3:\"url\";s:44:\"https://wordpress.org/plugins/query-monitor/\";s:7:\"package\";s:62:\"https://downloads.wordpress.org/plugin/query-monitor.3.1.0.zip\";s:5:\"icons\";a:1:{s:7:\"default\";s:64:\"https://s.w.org/plugins/geopattern-icon/query-monitor_525f62.svg\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:69:\"https://ps.w.org/query-monitor/assets/banner-1544x500.png?rev=1629576\";s:2:\"1x\";s:68:\"https://ps.w.org/query-monitor/assets/banner-772x250.png?rev=1731469\";}s:11:\"banners_rtl\";a:0:{}}}}','no');
/*!40000 ALTER TABLE `wp_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_postmeta`
--

DROP TABLE IF EXISTS `wp_postmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_postmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext,
  PRIMARY KEY (`meta_id`),
  KEY `post_id` (`post_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=704003000;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_postmeta`
--

LOCK TABLES `wp_postmeta` WRITE;
/*!40000 ALTER TABLE `wp_postmeta` DISABLE KEYS */;
INSERT INTO `wp_postmeta` VALUES (704001853,763,'location_icon','a:1:{s:4:\"icon\";s:0:\"\";}'),(704001856,765,'location_icon','a:1:{s:4:\"icon\";s:0:\"\";}'),(704002857,808,'_edit_last','1'),(704002858,808,'_edit_lock','1535978938:1'),(704002859,808,'location_ID','765'),(704002860,808,'item_ID','768'),(704002861,808,'datetime_part_period_start','1535932800'),(704002862,808,'datetime_part_period_end','1536019200'),(704002863,808,'recurrence_type','W'),(704002864,808,'datetime_from','1535932800'),(704002865,808,'period_group_ID','-- create new --'),(704002866,808,'period_status_type_ID','100000001'),(704002867,808,'period_ID','-- create new --'),(704002868,809,'periods',''),(704002869,809,'name','test'),(704002870,809,'zero_arrays',''),(704002871,809,'posts',''),(704002872,809,'post_status','publish'),(704002873,809,'post_password',''),(704002874,809,'post_author','0'),(704002875,809,'post_date','0000-00-00 00:00:00'),(704002876,809,'post_modified','0000-00-00 00:00:00'),(704002877,809,'post_excerpt',''),(704002878,809,'post_content',''),(704002879,809,'post_date_gmt','0000-00-00 00:00:00'),(704002880,809,'post_modified_gmt','0000-00-00 00:00:00'),(704002881,809,'post_title','test'),(704002882,809,'comment_status','open'),(704002883,809,'ping_status','open'),(704002884,809,'post_name',''),(704002885,809,'to_ping',''),(704002886,809,'pinged',''),(704002887,809,'post_content_filtered',''),(704002888,809,'post_parent','0'),(704002889,809,'guid',''),(704002890,809,'menu_order','0'),(704002891,809,'post_type','periodgroup'),(704002892,809,'post_mime_type',''),(704002893,809,'comment_count','0'),(704002894,809,'native_id','20'),(704002895,810,'name','test'),(704002896,810,'datetime_part_period_start','2018-09-03 00:00:00'),(704002897,810,'datetime_part_period_end','2018-09-04 00:00:00'),(704002898,810,'datetime_from','2018-09-03 00:00:00'),(704002899,810,'recurrence_type','W'),(704002900,810,'recurrence_frequency',''),(704002901,810,'recurrence_sequence',''),(704002902,810,'period_group_IDs',''),(704002903,810,'fullday',''),(704002904,810,'fullworkday',''),(704002905,810,'post_author','0'),(704002906,810,'post_date','0000-00-00 00:00:00'),(704002907,810,'post_date_gmt','0000-00-00 00:00:00'),(704002908,810,'post_content',''),(704002909,810,'post_title','test'),(704002910,810,'post_excerpt',''),(704002911,810,'post_status','publish'),(704002912,810,'comment_status','open'),(704002913,810,'ping_status','open'),(704002914,810,'post_password',''),(704002915,810,'post_name',''),(704002916,810,'to_ping',''),(704002917,810,'pinged',''),(704002918,810,'post_modified','0000-00-00 00:00:00'),(704002919,810,'post_modified_gmt','0000-00-00 00:00:00'),(704002920,810,'post_content_filtered',''),(704002921,810,'post_parent','0'),(704002922,810,'guid',''),(704002923,810,'menu_order','0'),(704002924,810,'post_type','period'),(704002925,810,'post_mime_type',''),(704002926,810,'comment_count','0'),(704002927,810,'native_id','10'),(704002928,808,'native_id','9'),(704002929,811,'_edit_last','1'),(704002930,811,'location_ID','763'),(704002931,811,'item_ID','767'),(704002932,811,'datetime_part_period_start','1536019200'),(704002933,811,'datetime_part_period_end','1536105600'),(704002934,811,'datetime_from','1536019200'),(704002935,811,'period_group_ID','-- create new --'),(704002936,811,'period_status_type_ID','100000001'),(704002937,811,'period_ID','-- create new --'),(704002938,812,'periods',''),(704002939,812,'name','fgdfghdfgh'),(704002940,812,'zero_arrays',''),(704002941,812,'posts',''),(704002942,812,'post_status','publish'),(704002943,812,'post_password',''),(704002944,812,'post_author','0'),(704002945,812,'post_date','0000-00-00 00:00:00'),(704002946,812,'post_modified','0000-00-00 00:00:00'),(704002947,812,'post_excerpt',''),(704002948,812,'post_content',''),(704002949,812,'post_date_gmt','0000-00-00 00:00:00'),(704002950,812,'post_modified_gmt','0000-00-00 00:00:00'),(704002951,812,'post_title','fgdfghdfgh'),(704002952,812,'comment_status','open'),(704002953,812,'ping_status','open'),(704002954,812,'post_name',''),(704002955,812,'to_ping',''),(704002956,812,'pinged',''),(704002957,812,'post_content_filtered',''),(704002958,812,'post_parent','0'),(704002959,812,'guid',''),(704002960,812,'menu_order','0'),(704002961,812,'post_type','periodgroup'),(704002962,812,'post_mime_type',''),(704002963,812,'comment_count','0'),(704002964,812,'native_id','21'),(704002965,813,'name','fgdfghdfgh'),(704002966,813,'datetime_part_period_start','2018-09-04 00:00:00'),(704002967,813,'datetime_part_period_end','2018-09-05 00:00:00'),(704002968,813,'datetime_from','2018-09-04 00:00:00'),(704002969,813,'recurrence_type',''),(704002970,813,'recurrence_frequency',''),(704002971,813,'recurrence_sequence',''),(704002972,813,'period_group_IDs',''),(704002973,813,'fullday',''),(704002974,813,'fullworkday',''),(704002975,813,'post_author','0'),(704002976,813,'post_date','0000-00-00 00:00:00'),(704002977,813,'post_date_gmt','0000-00-00 00:00:00'),(704002978,813,'post_content',''),(704002979,813,'post_title','fgdfghdfgh'),(704002980,813,'post_excerpt',''),(704002981,813,'post_status','publish'),(704002982,813,'comment_status','open'),(704002983,813,'ping_status','open'),(704002984,813,'post_password',''),(704002985,813,'post_name',''),(704002986,813,'to_ping',''),(704002987,813,'pinged',''),(704002988,813,'post_modified','0000-00-00 00:00:00'),(704002989,813,'post_modified_gmt','0000-00-00 00:00:00'),(704002990,813,'post_content_filtered',''),(704002991,813,'post_parent','0'),(704002992,813,'guid',''),(704002993,813,'menu_order','0'),(704002994,813,'post_type','period'),(704002995,813,'post_mime_type',''),(704002996,813,'comment_count','0'),(704002997,813,'native_id','11'),(704002998,811,'native_id','10'),(704002999,1100000010,'_edit_lock','1536050769:1');
/*!40000 ALTER TABLE `wp_postmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_posts`
--

DROP TABLE IF EXISTS `wp_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_posts` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_author` bigint(20) unsigned NOT NULL DEFAULT '0',
  `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content` longtext NOT NULL,
  `post_title` text NOT NULL,
  `post_excerpt` text NOT NULL,
  `post_status` varchar(20) NOT NULL DEFAULT 'publish',
  `comment_status` varchar(20) NOT NULL DEFAULT 'open',
  `ping_status` varchar(20) NOT NULL DEFAULT 'open',
  `post_password` varchar(255) NOT NULL DEFAULT '',
  `post_name` varchar(200) NOT NULL DEFAULT '',
  `to_ping` text NOT NULL,
  `pinged` text NOT NULL,
  `post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content_filtered` longtext NOT NULL,
  `post_parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `guid` varchar(255) NOT NULL DEFAULT '',
  `menu_order` int(11) NOT NULL DEFAULT '0',
  `post_type` varchar(20) NOT NULL DEFAULT 'post',
  `post_mime_type` varchar(100) NOT NULL DEFAULT '',
  `comment_count` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `post_name` (`post_name`(191)),
  KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`),
  KEY `post_parent` (`post_parent`),
  KEY `post_author` (`post_author`)
) ENGINE=InnoDB AUTO_INCREMENT=814;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_posts`
--

LOCK TABLES `wp_posts` WRITE;
/*!40000 ALTER TABLE `wp_posts` DISABLE KEYS */;
INSERT INTO `wp_posts` VALUES (1,1,'2018-05-03 10:00:31','2018-05-03 10:00:31','Welcome to WordPress. This is your first post. Edit or delete it, then start writing!','Hello world!','','publish','open','open','','hello-world','','','2018-08-30 07:01:51','2018-08-30 07:01:51','',0,'http://commonsbooking.localhost/?p=1',0,'post','',1),(2,1,'2018-05-03 10:00:31','2018-05-03 10:00:31','This is an example page. It\'s different from a blog post because it will stay in one place and will show up in your site navigation (in most themes). Most people start with an About page that introduces them to potential site visitors. It might say something like this:\n\n<blockquote>Hi there! I\'m a bike messenger by day, aspiring actor by night, and this is my website. I live in Los Angeles, have a great dog named Jack, and I like pi&#241;a coladas. (And gettin\' caught in the rain.)</blockquote>\n\n...or something like this:\n\n<blockquote>The XYZ Doohickey Company was founded in 1971, and has been providing quality doohickeys to the public ever since. Located in Gotham City, XYZ employs over 2,000 people and does all kinds of awesome things for the Gotham community.</blockquote>\n\nAs a new WordPress user, you should go to <a href=\"http://commonsbooking.localhost/wp-admin/\">your dashboard</a> to delete this page and create new pages for your content. Have fun!','Sample Page','','publish','closed','open','','sample-page','','','2018-05-03 10:00:31','2018-05-03 10:00:31','',0,'http://commonsbooking.localhost/?page_id=2',0,'page','',0),(763,1,'2018-08-31 14:35:17','2018-08-31 14:35:17','','CargoNomia','','publish','closed','closed','','cargonomia','','','2018-08-31 14:35:17','2018-08-31 14:35:17','',0,'http://commonsbooking.localhost/?post_type=location&#038;p=763',0,'location','',0),(765,1,'2018-08-31 14:36:41','2018-08-31 14:36:41','','Berlin place','','publish','closed','closed','','berlin-place','','','2018-08-31 14:36:41','2018-08-31 14:36:41','',0,'http://commonsbooking.localhost/?post_type=location&#038;p=765',0,'location','',0),(767,1,'2018-08-31 14:39:48','2018-08-31 14:39:48','','Bicycle','','publish','closed','closed','','bicycle','','','2018-08-31 14:39:48','2018-08-31 14:39:48','',0,'http://commonsbooking.localhost/?post_type=item&#038;p=767',0,'item','',0),(768,1,'2018-08-31 14:39:57','2018-08-31 14:39:57','','Trailer','','publish','closed','closed','','trailer','','','2018-08-31 14:39:57','2018-08-31 14:39:57','',0,'http://commonsbooking.localhost/?post_type=item&#038;p=768',0,'item','',0),(808,1,'2018-09-03 12:49:08','2018-09-03 12:49:08','','test','','publish','closed','closed','','test','','','2018-09-03 12:49:08','2018-09-03 12:49:08','',0,'http://commonsbooking.localhost/?post_type=periodent-timeframe&#038;p=808',0,'periodent-timeframe','',0),(809,1,'2018-09-03 12:49:10','2018-09-03 12:49:10','','test','','publish','closed','closed','','test','','','2018-09-03 12:49:10','2018-09-03 12:49:10','',0,'http://commonsbooking.localhost/?post_type=periodgroup&#038;p=809',0,'periodgroup','',0),(810,1,'2018-09-03 12:49:12','2018-09-03 12:49:12','','test','','publish','closed','closed','','test','','','2018-09-03 12:49:12','2018-09-03 12:49:12','',0,'http://commonsbooking.localhost/?post_type=period&#038;p=810',0,'period','',0),(811,1,'2018-09-04 08:45:55','2018-09-04 08:45:55','','fgdfghdfgh','','publish','closed','closed','','fgdfghdfgh','','','2018-09-04 08:45:55','2018-09-04 08:45:55','',0,'http://commonsbooking.localhost/?post_type=periodent-timeframe&#038;p=811',0,'periodent-timeframe','',0),(812,1,'2018-09-04 08:45:58','2018-09-04 08:45:58','','fgdfghdfgh','','publish','closed','closed','','fgdfghdfgh','','','2018-09-04 08:45:58','2018-09-04 08:45:58','',0,'http://commonsbooking.localhost/?post_type=periodgroup&#038;p=812',0,'periodgroup','',0),(813,1,'2018-09-04 08:46:01','2018-09-04 08:46:01','','fgdfghdfgh','','publish','closed','closed','','fgdfghdfgh','','','2018-09-04 08:46:01','2018-09-04 08:46:01','',0,'http://commonsbooking.localhost/?post_type=period&#038;p=813',0,'period','',0);
/*!40000 ALTER TABLE `wp_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_query_override_terms`
--

DROP TABLE IF EXISTS `wp_query_override_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_query_override_terms` (
  `query_id` mediumint(9) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  UNIQUE KEY `query_term` (`query_id`,`term_id`)
) ENGINE=InnoDB;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_query_override_terms`
--

LOCK TABLES `wp_query_override_terms` WRITE;
/*!40000 ALTER TABLE `wp_query_override_terms` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_query_override_terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_query_wrangler`
--

DROP TABLE IF EXISTS `wp_query_wrangler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_query_wrangler` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `type` varchar(16) NOT NULL,
  `path` varchar(255) DEFAULT NULL,
  `data` mediumtext NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_query_wrangler`
--

LOCK TABLES `wp_query_wrangler` WRITE;
/*!40000 ALTER TABLE `wp_query_wrangler` DISABLE KEYS */;
INSERT INTO `wp_query_wrangler` VALUES (1,'booking table','booking-table','widget',NULL,'a:2:{s:7:\"display\";a:11:{s:5:\"title\";s:12:\"Availability\";s:5:\"style\";s:5:\"table\";s:9:\"row_style\";s:13:\"template_part\";s:13:\"post_settings\";a:1:{s:4:\"size\";s:8:\"complete\";}s:14:\"field_settings\";a:3:{s:14:\"group_by_field\";s:8:\"__none__\";s:20:\"strip_group_by_field\";s:0:\"\";s:6:\"fields\";a:4:{s:2:\"ID\";a:9:{s:4:\"type\";s:2:\"ID\";s:8:\"hook_key\";s:2:\"ID\";s:4:\"name\";s:2:\"ID\";s:6:\"weight\";s:1:\"0\";s:9:\"has_label\";s:2:\"on\";s:5:\"label\";s:2:\"ID\";s:13:\"custom_output\";s:0:\"\";s:7:\"classes\";s:0:\"\";s:19:\"empty_field_content\";s:0:\"\";}s:10:\"post_title\";a:10:{s:4:\"type\";s:10:\"post_title\";s:8:\"hook_key\";s:10:\"post_title\";s:4:\"name\";s:10:\"post_title\";s:6:\"weight\";s:1:\"1\";s:4:\"link\";s:2:\"on\";s:9:\"has_label\";s:2:\"on\";s:5:\"label\";s:5:\"title\";s:13:\"custom_output\";s:0:\"\";s:7:\"classes\";s:0:\"\";s:19:\"empty_field_content\";s:0:\"\";}s:12:\"post_content\";a:9:{s:4:\"type\";s:12:\"post_content\";s:8:\"hook_key\";s:12:\"post_content\";s:4:\"name\";s:12:\"post_content\";s:6:\"weight\";s:1:\"2\";s:9:\"has_label\";s:2:\"on\";s:5:\"label\";s:7:\"content\";s:13:\"custom_output\";s:0:\"\";s:7:\"classes\";s:0:\"\";s:19:\"empty_field_content\";s:0:\"\";}s:9:\"post_date\";a:9:{s:4:\"type\";s:9:\"post_date\";s:8:\"hook_key\";s:9:\"post_date\";s:4:\"name\";s:9:\"post_date\";s:6:\"weight\";s:1:\"3\";s:9:\"has_label\";s:2:\"on\";s:5:\"label\";s:4:\"date\";s:13:\"custom_output\";s:0:\"\";s:7:\"classes\";s:0:\"\";s:19:\"empty_field_content\";s:0:\"\";}}}s:22:\"template_part_settings\";a:2:{s:4:\"path\";s:44:\"../../plugins/commons-booking/templates/list\";s:4:\"name\";s:14:\"week-available\";}s:6:\"header\";s:42:\"Select your booking days and click Book :)\";s:6:\"footer\";s:0:\"\";s:5:\"empty\";s:0:\"\";s:15:\"wrapper-classes\";s:0:\"\";s:4:\"page\";a:1:{s:5:\"pager\";a:3:{s:4:\"type\";s:7:\"default\";s:8:\"previous\";s:0:\"\";s:4:\"next\";s:0:\"\";}}}s:4:\"args\";a:5:{s:14:\"posts_per_page\";s:2:\"-1\";s:11:\"post_status\";s:7:\"publish\";s:6:\"offset\";i:0;s:5:\"sorts\";a:1:{s:4:\"date\";a:5:{s:4:\"type\";s:4:\"date\";s:8:\"hook_key\";s:9:\"post_date\";s:4:\"name\";s:4:\"date\";s:6:\"weight\";s:1:\"0\";s:11:\"order_value\";s:3:\"ASC\";}}s:7:\"filters\";a:2:{s:10:\"post_types\";a:9:{s:4:\"type\";s:10:\"post_types\";s:8:\"hook_key\";s:10:\"post_types\";s:4:\"name\";s:10:\"post_types\";s:6:\"weight\";s:1:\"0\";s:10:\"post_types\";a:4:{s:17:\"perioditem-global\";s:17:\"perioditem-global\";s:19:\"perioditem-location\";s:19:\"perioditem-location\";s:20:\"perioditem-timeframe\";s:20:\"perioditem-timeframe\";s:15:\"perioditem-user\";s:15:\"perioditem-user\";}s:13:\"exposed_label\";s:0:\"\";s:12:\"exposed_desc\";s:0:\"\";s:11:\"exposed_key\";s:0:\"\";s:16:\"exposed_settings\";a:1:{s:4:\"type\";s:6:\"select\";}}s:8:\"callback\";a:5:{s:4:\"type\";s:8:\"callback\";s:8:\"hook_key\";s:8:\"callback\";s:4:\"name\";s:8:\"callback\";s:6:\"weight\";s:1:\"1\";s:8:\"callback\";s:39:\"cb2_query_wrangler_date_filter_callback\";}}}}');
/*!40000 ALTER TABLE `wp_query_wrangler` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_term_relationships`
--

DROP TABLE IF EXISTS `wp_term_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_term_relationships` (
  `object_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `term_taxonomy_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `term_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`object_id`,`term_taxonomy_id`),
  KEY `term_taxonomy_id` (`term_taxonomy_id`)
) ENGINE=InnoDB;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_term_relationships`
--

LOCK TABLES `wp_term_relationships` WRITE;
/*!40000 ALTER TABLE `wp_term_relationships` DISABLE KEYS */;
INSERT INTO `wp_term_relationships` VALUES (1,1,0),(6,1,0),(10,1,0);
/*!40000 ALTER TABLE `wp_term_relationships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_term_taxonomy`
--

DROP TABLE IF EXISTS `wp_term_taxonomy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_term_taxonomy` (
  `term_taxonomy_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `taxonomy` varchar(32) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `count` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`term_taxonomy_id`),
  UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
  KEY `taxonomy` (`taxonomy`)
) ENGINE=InnoDB AUTO_INCREMENT=2;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_term_taxonomy`
--

LOCK TABLES `wp_term_taxonomy` WRITE;
/*!40000 ALTER TABLE `wp_term_taxonomy` DISABLE KEYS */;
INSERT INTO `wp_term_taxonomy` VALUES (1,1,'category','',0,1);
/*!40000 ALTER TABLE `wp_term_taxonomy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_termmeta`
--

DROP TABLE IF EXISTS `wp_termmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_termmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext,
  PRIMARY KEY (`meta_id`),
  KEY `term_id` (`term_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_termmeta`
--

LOCK TABLES `wp_termmeta` WRITE;
/*!40000 ALTER TABLE `wp_termmeta` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_termmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_terms`
--

DROP TABLE IF EXISTS `wp_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_terms` (
  `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT '',
  `term_group` bigint(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`term_id`),
  KEY `slug` (`slug`(191)),
  KEY `name` (`name`(191))
) ENGINE=InnoDB AUTO_INCREMENT=2;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_terms`
--

LOCK TABLES `wp_terms` WRITE;
/*!40000 ALTER TABLE `wp_terms` DISABLE KEYS */;
INSERT INTO `wp_terms` VALUES (1,'Uncategorized','uncategorized',0);
/*!40000 ALTER TABLE `wp_terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_usermeta`
--

DROP TABLE IF EXISTS `wp_usermeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_usermeta` (
  `umeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext,
  PRIMARY KEY (`umeta_id`),
  KEY `user_id` (`user_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=26;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_usermeta`
--

LOCK TABLES `wp_usermeta` WRITE;
/*!40000 ALTER TABLE `wp_usermeta` DISABLE KEYS */;
INSERT INTO `wp_usermeta` VALUES (1,1,'nickname','anewholm'),(2,1,'first_name',''),(3,1,'last_name',''),(4,1,'description',''),(5,1,'rich_editing','true'),(6,1,'syntax_highlighting','true'),(7,1,'comment_shortcuts','false'),(8,1,'admin_color','fresh'),(9,1,'use_ssl','0'),(10,1,'show_admin_bar_front','true'),(11,1,'locale',''),(12,1,'wp_capabilities','a:1:{s:13:\"administrator\";b:1;}'),(13,1,'wp_user_level','10'),(14,1,'dismissed_wp_pointers','wp496_privacy'),(15,1,'show_welcome_panel','1'),(16,1,'session_tokens','a:1:{s:64:\"3f844f309ed8225fa587f8b2f7f8f1f14377b20de9be426b21143665ecde48de\";a:4:{s:10:\"expiration\";i:1536056105;s:2:\"ip\";s:3:\"::1\";s:2:\"ua\";s:104:\"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36\";s:5:\"login\";i:1535883305;}}'),(17,1,'wp_dashboard_quick_press_last_post_id','313'),(18,1,'community-events-location','a:1:{s:2:\"ip\";s:2:\"::\";}'),(19,1,'wp_user-settings','libraryContent=browse&mfold=o'),(20,1,'wp_user-settings-time','1534433451'),(21,1,'closedpostboxes_admin_page_cb-post-new','a:0:{}'),(22,1,'metaboxhidden_admin_page_cb-post-new','a:1:{i:0;s:7:\"slugdiv\";}'),(23,1,'closedpostboxes_periodent-global','a:0:{}'),(24,1,'metaboxhidden_periodent-global','a:1:{i:0;s:7:\"slugdiv\";}'),(25,1,'meta-box-order_admin_page_cb-post-new','a:3:{s:4:\"side\";s:89:\"submitdiv,CB_Period_metabox_1,CB_Period_metabox_2,CB_Period_metabox_4,CB_Period_metabox_5\";s:6:\"normal\";s:47:\"slugdiv,CB_Period_metabox_0,CB_Period_metabox_3\";s:8:\"advanced\";s:0:\"\";}');
/*!40000 ALTER TABLE `wp_usermeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_users`
--

DROP TABLE IF EXISTS `wp_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

CREATE TABLE `wp_users` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_login` varchar(60) NOT NULL DEFAULT '',
  `user_pass` varchar(255) NOT NULL DEFAULT '',
  `user_nicename` varchar(50) NOT NULL DEFAULT '',
  `user_email` varchar(100) NOT NULL DEFAULT '',
  `user_url` varchar(100) NOT NULL DEFAULT '',
  `user_registered` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_activation_key` varchar(255) NOT NULL DEFAULT '',
  `user_status` int(11) NOT NULL DEFAULT '0',
  `display_name` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `user_login_key` (`user_login`),
  KEY `user_nicename` (`user_nicename`),
  KEY `user_email` (`user_email`)
) ENGINE=InnoDB AUTO_INCREMENT=2;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_users`
--

LOCK TABLES `wp_users` WRITE;
/*!40000 ALTER TABLE `wp_users` DISABLE KEYS */;
INSERT INTO `wp_users` VALUES (1,'anewholm','$P$BYViZKjq1YLkzx1OlSypWyGoNbhAXo.','anewholm','annesley_newholm@yahoo.it','','2018-05-03 10:00:29','',0,'anewholm');
/*!40000 ALTER TABLE `wp_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'commonsbooking_2'
--

--
-- Dumping routines for database 'commonsbooking_2'
--
/*!50003 DROP PROCEDURE IF EXISTS `cb2_book` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE  PROCEDURE `cb2_book`(param_location_post_id int, param_item_post_id int, param_user_id int, param_datetime_from datetime, param_datetime_to datetime, out new_period_group_id int)
BEGIN
	declare collect_flag bit(1);
	declare use_flag bit(1);
	declare return_flag bit(1);

	declare can_collect bit(1);
	declare can_return bit(1);
	declare can_use bit(1);
    
    declare new_period_id int;

	# Constants: flag meanings
    select collect_flag = b'001', use_flag = b'010', return_flag = b'100';
    
	# Check for any periods that prevent booking
    # System periods:
	# '1', 'available'
	# '2', 'booked'
	# '3', 'closed'
	# '4', 'open'
	# '5', 'repair'
    # Check the Global, Location and Item status
    # The highest priority period dictates the availability
    # For example: "closed" has a higher priority than "available"
    #   "open" has a higher priority than "closed"
    select can_collect = flags & collect_flag
		from wp_cb2_view_calendar_period_items cal
		where location_post_id in(null, param_location_post_id)
		and item_post_id in(null, param_item_post_id)
		and datetime_from < cal.date and datetime_from > cal.date
        order by priority desc limit 1;
    select can_return = flags & return_flag from wp_cb2_view_calendar_period_items cal
		where location_post_id in(null, param_location_post_id)
		and item_post_id in(null, param_item_post_id)
		and datetime_to < cal.date and datetime_to > cal.date
        order by priority desc limit 1;
    select can_use = flags & use_flag from wp_cb2_view_calendar_period_items cal
		where location_post_id in(null, param_location_post_id)
		and item_post_id in(null, param_item_post_id)
		and datetime_from < cal.date and datetime_to > cal.date
        order by priority desc limit 1;
        
	if can_collect = 1 and can_use = 1 and can_return = 1 then
		insert into wp_cb2_periods(period_status_type_id, `name`, datetime_part_period_start, datetime_part_period_end)
			values(2, 'booked', datetime_from, datetime_to);
		select new_period_id = LAST_INSERT_ID();
		insert into wp_cb2_period_groups(`name`) values('booked');
		select new_period_group_id = LAST_INSERT_ID();
		insert into wp_cb2_period_group_period(period_group_id, period_id)
			values(new_period_group_id, new_period_id);
    end if; 
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `cb2_book_available_slot` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE  PROCEDURE `cb2_book_available_slot`(in period_id int, in recurrence_index int)
BEGIN
	# Book based on availability only
    # Calls the associated general period creation procedure
    declare datetime_from datetime;
    declare datetime_to datetime;
    declare location_post_id bigint(20);
    declare item_post_id bigint(20);
    declare user_id bigint(20);
    
    select datetime_from = '2018-05-01 09:00:00', datetime_to = '2018-05-01 18:00:00';
    
    call cb2_book(location_post_id, item_post_id, user_id, datetime_from, datetime_to);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `cb2_periodoccurrence_update` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;



/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE  PROCEDURE `cb2_periodoccurrence_update`(meta_value varchar(255), meta_key longtext)
BEGIN

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Final view structure for view `wp_cb2_view_future_bookings`
--

/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_future_bookings`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;



/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `wp_cb2_view_future_bookings` AS select `po`.`timeframe_id` AS `timeframe_id`,`po`.`period_id` AS `period_id` from `wp_cb2_view_perioditem_entities` `po` where ((`po`.`datetime_period_item_start` > now()) and (`po`.`period_group_type` = 'user') and (`po`.`period_status_type_id` = 2)) group by `po`.`timeframe_id`,`po`.`period_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `wp_cb2_view_period_entities`
--

/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_period_entities`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;



/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `wp_cb2_view_period_entities` AS select `ip`.`global_period_group_id` AS `timeframe_id`,`pg`.`name` AS `title`,NULL AS `location_ID`,NULL AS `item_ID`,NULL AS `user_ID`,'global' AS `period_group_type`,1 AS `period_group_priority`,`ip`.`period_group_id` AS `period_group_id`,`ip`.`period_status_type_id` AS `period_status_type_id` from (`wp_cb2_global_period_groups` `ip` join `wp_cb2_period_groups` `pg` on((`ip`.`period_group_id` = `pg`.`period_group_id`))) union all select `ip`.`location_period_group_id` AS `timeframe_ID`,concat(`pg`.`name`,' - ',`loc`.`post_title`) AS `title`,`ip`.`location_ID` AS `location_ID`,NULL AS `item_ID`,NULL AS `user_ID`,'location' AS `period_group_type`,2 AS `period_group_priority`,`ip`.`period_group_id` AS `period_group_id`,`ip`.`period_status_type_id` AS `period_status_type_id` from ((`wp_cb2_location_period_groups` `ip` join `wp_cb2_period_groups` `pg` on((`ip`.`period_group_id` = `pg`.`period_group_id`))) join `wp_posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) union all select `ip`.`timeframe_period_group_id` AS `timeframe_ID`,concat(`pg`.`name`,' - ',`loc`.`post_title`,' - ',`itm`.`post_title`) AS `title`,`ip`.`location_ID` AS `location_ID`,`ip`.`item_ID` AS `item_ID`,NULL AS `user_ID`,'timeframe' AS `period_group_type`,3 AS `period_group_priority`,`ip`.`period_group_id` AS `period_group_id`,`ip`.`period_status_type_id` AS `period_status_type_id` from (((`wp_cb2_timeframe_period_groups` `ip` join `wp_cb2_period_groups` `pg` on((`ip`.`period_group_id` = `pg`.`period_group_id`))) join `wp_posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `wp_posts` `itm` on((`ip`.`item_ID` = `itm`.`ID`))) union all select `ip`.`timeframe_user_period_group_id` AS `timeframe_ID`,concat(`pg`.`name`,' - ',`loc`.`post_title`,' - ',`itm`.`post_title`,' - ',`usr`.`user_login`) AS `title`,`ip`.`location_ID` AS `location_ID`,`ip`.`item_ID` AS `item_ID`,`ip`.`user_id` AS `user_ID`,'user' AS `period_group_type`,4 AS `period_group_priority`,`ip`.`period_group_id` AS `period_group_id`,`ip`.`period_status_type_id` AS `period_status_type_id` from ((((`wp_cb2_timeframe_user_period_groups` `ip` join `wp_cb2_period_groups` `pg` on((`ip`.`period_group_id` = `pg`.`period_group_id`))) join `wp_posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `wp_posts` `itm` on((`ip`.`item_ID` = `itm`.`ID`))) join `wp_users` `usr` on((`ip`.`user_id` = `usr`.`ID`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `wp_cb2_view_period_posts`
--

/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_period_posts`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;



/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `wp_cb2_view_period_posts` AS select (`p`.`period_id` + `pt_p`.`ID_base`) AS `ID`,1 AS `post_author`,`p`.`datetime_from` AS `post_date`,`p`.`datetime_from` AS `post_date_gmt`,`p`.`description` AS `post_content`,`p`.`name` AS `post_title`,'' AS `post_excerpt`,'publish' AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(`p`.`period_id` + `pt_p`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,`p`.`datetime_to` AS `post_modified`,`p`.`datetime_to` AS `post_modified_gmt`,'' AS `post_content_filtered`,0 AS `post_parent`,'' AS `guid`,0 AS `menu_order`,'period' AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`p`.`period_id` AS `period_id`,`p`.`datetime_part_period_start` AS `datetime_part_period_start`,`p`.`datetime_part_period_end` AS `datetime_part_period_end`,`p`.`datetime_from` AS `datetime_from`,`p`.`datetime_to` AS `datetime_to`,`p`.`recurrence_type` AS `recurrence_type`,`p`.`recurrence_sequence` AS `recurrence_sequence`,`p`.`recurrence_frequency` AS `recurrence_frequency`,(select group_concat((`pgp`.`period_group_id` + `pt`.`ID_base`) separator ',') from (`wp_cb2_period_group_period` `pgp` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = 'periodgroup'))) where (`pgp`.`period_id` = `p`.`period_id`)) AS `period_group_IDs` from ((`wp_cb2_periods` `p` join `wp_cb2_post_types` `pt_p` on((`pt_p`.`post_type` = 'period'))) join `wp_cb2_post_types` `pt_pst` on((`pt_pst`.`post_type` = 'periodstatustype'))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `wp_cb2_view_periodent_posts`
--

/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_periodent_posts`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;



/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `wp_cb2_view_periodent_posts` AS select (`p`.`timeframe_id` + `pt_e`.`ID_base`) AS `ID`,1 AS `post_author`,'2018-01-01' AS `post_date`,'2018-01-01' AS `post_date_gmt`,'' AS `post_content`,`p`.`title` AS `post_title`,'' AS `post_excerpt`,'publish' AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(`p`.`timeframe_id` + `pt_e`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,'2018-01-01' AS `post_modified`,'2018-01-01' AS `post_modified_gmt`,'' AS `post_content_filtered`,0 AS `post_parent`,'' AS `guid`,0 AS `menu_order`,concat('periodent-',`p`.`period_group_type`) AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`p`.`timeframe_id` AS `timeframe_id`,`p`.`location_ID` AS `location_ID`,`p`.`item_ID` AS `item_ID`,`p`.`user_ID` AS `user_ID`,(`p`.`period_group_id` + `pt_pg`.`ID_base`) AS `period_group_ID`,(`p`.`period_status_type_id` + `pt_pst`.`ID_base`) AS `period_status_type_ID`,(select group_concat((`wp_cb2_period_group_period`.`period_id` + `pt2`.`ID_base`) separator ',') from (`wp_cb2_period_group_period` join `wp_cb2_post_types` `pt2` on((`pt2`.`post_type` = 'period'))) where (`wp_cb2_period_group_period`.`period_group_id` = `p`.`period_group_id`) group by `wp_cb2_period_group_period`.`period_group_id`) AS `period_IDs` from (((`wp_cb2_view_period_entities` `p` join `wp_cb2_post_types` `pt_e` on((`pt_e`.`post_type` = concat('periodent-',`p`.`period_group_type`)))) join `wp_cb2_post_types` `pt_pg` on((`pt_pg`.`post_type` = 'periodgroup'))) join `wp_cb2_post_types` `pt_pst` on((`pt_pst`.`post_type` = 'periodstatustype'))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `wp_cb2_view_periodentmeta`
--

/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_periodentmeta`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;



/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `wp_cb2_view_periodentmeta` AS select ((`po`.`timeframe_id` * 10) + `pt`.`ID_base`) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'period_IDs' AS `meta_key`,`po`.`period_IDs` AS `meta_value` from (`wp_cb2_view_periodent_posts` `po` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select (((`po`.`timeframe_id` * 10) + `pt`.`ID_base`) + 1) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'period_group_ID' AS `meta_key`,`po`.`period_group_ID` AS `meta_value` from (`wp_cb2_view_periodent_posts` `po` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select (((`po`.`timeframe_id` * 10) + `pt`.`ID_base`) + 2) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'period_status_type_ID' AS `meta_key`,`po`.`period_status_type_ID` AS `meta_value` from (`wp_cb2_view_periodent_posts` `po` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select (((`po`.`timeframe_id` * 10) + `pt`.`ID_base`) + 3) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'location_ID' AS `meta_key`,`po`.`location_ID` AS `meta_value` from (`wp_cb2_view_periodent_posts` `po` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select (((`po`.`timeframe_id` * 10) + `pt`.`ID_base`) + 4) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'item_ID' AS `meta_key`,`po`.`item_ID` AS `meta_value` from (`wp_cb2_view_periodent_posts` `po` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) union all select (((`po`.`timeframe_id` * 10) + `pt`.`ID_base`) + 5) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodent_id`,'user_ID' AS `meta_key`,`po`.`user_ID` AS `meta_value` from (`wp_cb2_view_periodent_posts` `po` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `po`.`post_type`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `wp_cb2_view_periodgroup_posts`
--

/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_periodgroup_posts`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;



/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `wp_cb2_view_periodgroup_posts` AS select (`p`.`period_group_id` + `pt_pg`.`ID_base`) AS `ID`,1 AS `post_author`,'2018-01-01' AS `post_date`,'2018-01-01' AS `post_date_gmt`,`p`.`description` AS `post_content`,`p`.`name` AS `post_title`,'' AS `post_excerpt`,'publish' AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(`p`.`period_group_id` + `pt_pg`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,'2018-01-01' AS `post_modified`,'2018-01-01' AS `post_modified_gmt`,'' AS `post_content_filtered`,0 AS `post_parent`,'' AS `guid`,0 AS `menu_order`,'periodgroup' AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`p`.`period_group_id` AS `period_group_id`,(select group_concat((`wp_cb2_period_group_period`.`period_id` + `pt2`.`ID_base`) separator ',') from (`wp_cb2_period_group_period` join `wp_cb2_post_types` `pt2` on((`pt2`.`post_type` = 'period'))) where (`wp_cb2_period_group_period`.`period_group_id` = `p`.`period_group_id`) group by `wp_cb2_period_group_period`.`period_group_id`) AS `period_IDs` from (`wp_cb2_period_groups` `p` join `wp_cb2_post_types` `pt_pg` on((`pt_pg`.`post_type` = 'periodgroup'))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `wp_cb2_view_periodgroupmeta`
--

/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_periodgroupmeta`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;



/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `wp_cb2_view_periodgroupmeta` AS select ((`po`.`period_group_id` * 10) + `pt`.`ID_base`) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodgroup_id`,'period_IDs' AS `meta_key`,`po`.`period_IDs` AS `meta_value` from (`wp_cb2_view_periodgroup_posts` `po` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = 'periodgroup'))) union all select (((`po`.`period_group_id` * 10) + `pt`.`ID_base`) + 1) AS `meta_id`,`po`.`ID` AS `post_id`,`po`.`ID` AS `periodgroup_id`,'period_group_id' AS `meta_key`,`po`.`period_group_id` AS `meta_value` from (`wp_cb2_view_periodgroup_posts` `po` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = 'periodgroup'))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `wp_cb2_view_perioditem_entities`
--

/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_perioditem_entities`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;



/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `wp_cb2_view_perioditem_entities` AS select `ip`.`global_period_group_id` AS `timeframe_id`,concat(`pst`.`name`) AS `title`,NULL AS `location_ID`,NULL AS `item_ID`,NULL AS `user_ID`,'global' AS `period_group_type`,1 AS `period_group_priority`,`pgp`.`period_group_id` AS `period_group_id`,`p`.`period_id` AS `period_id`,`pst`.`period_status_type_id` AS `period_status_type_id`,`po`.`recurrence_index` AS `recurrence_index`,`po`.`datetime_period_item_start` AS `datetime_period_item_start`,`po`.`datetime_period_item_end` AS `datetime_period_item_end` from ((((`wp_cb2_view_perioditems` `po` join `wp_cb2_periods` `p` on((`po`.`period_id` = `p`.`period_id`))) join `wp_cb2_period_group_period` `pgp` on((`pgp`.`period_id` = `p`.`period_id`))) join `wp_cb2_global_period_groups` `ip` on((`ip`.`period_group_id` = `pgp`.`period_group_id`))) join `wp_cb2_period_status_types` `pst` on((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`))) union all select `ip`.`location_period_group_id` AS `timeframe_ID`,concat(`loc`.`post_title`,' - ',`pst`.`name`) AS `title`,`ip`.`location_ID` AS `location_ID`,NULL AS `item_ID`,NULL AS `user_ID`,'location' AS `period_group_type`,2 AS `period_group_priority`,`pgp`.`period_group_id` AS `period_group_id`,`p`.`period_id` AS `period_id`,`pst`.`period_status_type_id` AS `period_status_type_id`,`po`.`recurrence_index` AS `recurrence_index`,`po`.`datetime_period_item_start` AS `datetime_period_item_start`,`po`.`datetime_period_item_end` AS `datetime_period_item_end` from (((((`wp_cb2_view_perioditems` `po` join `wp_cb2_periods` `p` on((`po`.`period_id` = `p`.`period_id`))) join `wp_cb2_period_group_period` `pgp` on((`pgp`.`period_id` = `p`.`period_id`))) join `wp_cb2_location_period_groups` `ip` on((`ip`.`period_group_id` = `pgp`.`period_group_id`))) join `wp_posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `wp_cb2_period_status_types` `pst` on((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`))) union all select `ip`.`timeframe_period_group_id` AS `timeframe_ID`,concat(`loc`.`post_title`,' - ',`itm`.`post_title`,' - ',`pst`.`name`) AS `title`,`ip`.`location_ID` AS `location_ID`,`ip`.`item_ID` AS `item_ID`,NULL AS `user_ID`,'timeframe' AS `period_group_type`,3 AS `period_group_priority`,`pgp`.`period_group_id` AS `period_group_id`,`p`.`period_id` AS `period_id`,`pst`.`period_status_type_id` AS `period_status_type_id`,`po`.`recurrence_index` AS `recurrence_index`,`po`.`datetime_period_item_start` AS `datetime_period_item_start`,`po`.`datetime_period_item_end` AS `datetime_period_item_end` from ((((((`wp_cb2_view_perioditems` `po` join `wp_cb2_periods` `p` on((`po`.`period_id` = `p`.`period_id`))) join `wp_cb2_period_group_period` `pgp` on((`pgp`.`period_id` = `p`.`period_id`))) join `wp_cb2_timeframe_period_groups` `ip` on((`ip`.`period_group_id` = `pgp`.`period_group_id`))) join `wp_posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `wp_posts` `itm` on((`ip`.`item_ID` = `itm`.`ID`))) join `wp_cb2_period_status_types` `pst` on((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`))) union all select `ip`.`timeframe_user_period_group_id` AS `timeframe_ID`,concat(`loc`.`post_title`,' - ',`itm`.`post_title`,' - ',`usr`.`user_login`,' - ',`pst`.`name`) AS `title`,`ip`.`location_ID` AS `location_ID`,`ip`.`item_ID` AS `item_ID`,`ip`.`user_id` AS `user_ID`,'user' AS `period_group_type`,4 AS `period_group_priority`,`pgp`.`period_group_id` AS `period_group_id`,`p`.`period_id` AS `period_id`,`pst`.`period_status_type_id` AS `period_status_type_id`,`po`.`recurrence_index` AS `recurrence_index`,`po`.`datetime_period_item_start` AS `datetime_period_item_start`,`po`.`datetime_period_item_end` AS `datetime_period_item_end` from (((((((`wp_cb2_view_perioditems` `po` join `wp_cb2_periods` `p` on((`po`.`period_id` = `p`.`period_id`))) join `wp_cb2_period_group_period` `pgp` on((`pgp`.`period_id` = `p`.`period_id`))) join `wp_cb2_timeframe_user_period_groups` `ip` on((`ip`.`period_group_id` = `pgp`.`period_group_id`))) join `wp_posts` `loc` on((`ip`.`location_ID` = `loc`.`ID`))) join `wp_posts` `itm` on((`ip`.`item_ID` = `itm`.`ID`))) join `wp_users` `usr` on((`ip`.`user_id` = `usr`.`ID`))) join `wp_cb2_period_status_types` `pst` on((`ip`.`period_status_type_id` = `pst`.`period_status_type_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `wp_cb2_view_perioditem_posts`
--

/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_perioditem_posts`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;



/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `wp_cb2_view_perioditem_posts` AS select (`pt`.`ID_base` + `sq`.`num`) AS `ID`,1 AS `post_author`,`sq`.`datetime_start` AS `post_date`,`sq`.`datetime_start` AS `post_date_gmt`,'' AS `post_content`,'automatic' AS `post_title`,'' AS `post_excerpt`,'auto-draft' AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(`pt`.`ID_base` + `sq`.`num`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,`sq`.`datetime_end` AS `post_modified`,`sq`.`datetime_end` AS `post_modified_gmt`,'' AS `post_content_filtered`,NULL AS `post_parent`,'' AS `guid`,0 AS `menu_order`,'perioditem-automatic' AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,NULL AS `period_group_id`,NULL AS `period_id`,0 AS `recurrence_index`,NULL AS `timeframe_id`,NULL AS `period_entity_ID`,NULL AS `location_ID`,NULL AS `item_ID`,NULL AS `user_ID`,NULL AS `period_status_type_id`,`sq`.`datetime_start` AS `datetime_period_item_start`,`sq`.`datetime_end` AS `datetime_period_item_end` from (`wp_cb2_view_sequence_date` `sq` join `wp_cb2_post_types` `pt` on((`pt`.`post_type_id` = 3))) union all select (((`po`.`timeframe_id` * 100000) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `ID`,1 AS `post_author`,`po`.`datetime_period_item_start` AS `post_date`,`po`.`datetime_period_item_start` AS `post_date_gmt`,'' AS `post_content`,`po`.`title` AS `post_title`,'' AS `post_excerpt`,'publish' AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,(((`po`.`timeframe_id` * 100000) + `po`.`recurrence_index`) + `pt_pi`.`ID_base`) AS `post_name`,'' AS `to_ping`,'' AS `pinged`,`po`.`datetime_period_item_end` AS `post_modified`,`po`.`datetime_period_item_end` AS `post_modified_gmt`,'' AS `post_content_filtered`,(`po`.`period_id` + `pt_p`.`ID_base`) AS `post_parent`,'' AS `guid`,0 AS `menu_order`,concat('perioditem-',`po`.`period_group_type`) AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`po`.`period_group_id` AS `period_group_id`,`po`.`period_id` AS `period_id`,`po`.`recurrence_index` AS `recurrence_index`,`po`.`timeframe_id` AS `timeframe_id`,(`po`.`timeframe_id` + `pt_e`.`ID_base`) AS `period_entity_ID`,`po`.`location_ID` AS `location_ID`,`po`.`item_ID` AS `item_ID`,`po`.`user_ID` AS `user_ID`,`pst`.`period_status_type_id` AS `period_status_type_id`,`po`.`datetime_period_item_start` AS `datetime_period_item_start`,`po`.`datetime_period_item_end` AS `datetime_period_item_end` from (((((`wp_cb2_view_perioditem_entities` `po` left join `wp_cb2_periods` `p` on((`po`.`period_id` = `p`.`period_id`))) join `wp_cb2_post_types` `pt_pi` on((concat('perioditem-',`po`.`period_group_type`) = `pt_pi`.`post_type`))) join `wp_cb2_post_types` `pt_p` on((1 = `pt_p`.`post_type_id`))) left join `wp_cb2_period_status_types` `pst` on((`pst`.`period_status_type_id` = `po`.`period_status_type_id`))) join `wp_cb2_post_types` `pt_e` on((`pt_e`.`post_type` = concat('periodent-',`po`.`period_group_type`)))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `wp_cb2_view_perioditemmeta`
--

/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_perioditemmeta`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;



/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `wp_cb2_view_perioditemmeta` AS select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,((((`cal`.`timeframe_id` * 100000) + `cal`.`recurrence_index`) * 10) + `pt`.`ID_base`) AS `meta_id`,(((`cal`.`timeframe_id` * 100000) + `pt`.`ID_base`) + `cal`.`recurrence_index`) AS `post_id`,(((`cal`.`timeframe_id` * 100000) + `pt`.`ID_base`) + `cal`.`recurrence_index`) AS `perioditem_id`,'timeframe_id' AS `meta_key`,`cal`.`timeframe_id` AS `meta_value` from (`wp_cb2_view_perioditem_entities` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * 100000) + `cal`.`recurrence_index`) * 10) + `pt`.`ID_base`) + 1) AS `meta_id`,(((`cal`.`timeframe_id` * 100000) + `pt`.`ID_base`) + `cal`.`recurrence_index`) AS `post_id`,(((`cal`.`timeframe_id` * 100000) + `pt`.`ID_base`) + `cal`.`recurrence_index`) AS `perioditem_id`,'location_ID' AS `meta_key`,`cal`.`location_ID` AS `meta_value` from (`wp_cb2_view_perioditem_entities` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * 100000) + `cal`.`recurrence_index`) * 10) + `pt`.`ID_base`) + 2) AS `meta_id`,(((`cal`.`timeframe_id` * 100000) + `pt`.`ID_base`) + `cal`.`recurrence_index`) AS `post_id`,(((`cal`.`timeframe_id` * 100000) + `pt`.`ID_base`) + `cal`.`recurrence_index`) AS `perioditem_id`,'item_ID' AS `meta_key`,`cal`.`item_ID` AS `meta_value` from (`wp_cb2_view_perioditem_entities` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * 100000) + `cal`.`recurrence_index`) * 10) + `pt`.`ID_base`) + 3) AS `meta_id`,(((`cal`.`timeframe_id` * 100000) + `pt`.`ID_base`) + `cal`.`recurrence_index`) AS `post_id`,(((`cal`.`timeframe_id` * 100000) + `pt`.`ID_base`) + `cal`.`recurrence_index`) AS `perioditem_id`,'user_ID' AS `meta_key`,`cal`.`user_ID` AS `meta_value` from (`wp_cb2_view_perioditem_entities` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * 100000) + `cal`.`recurrence_index`) * 10) + `pt_pi`.`ID_base`) + 4) AS `meta_id`,(((`cal`.`timeframe_id` * 100000) + `pt_pi`.`ID_base`) + `cal`.`recurrence_index`) AS `post_id`,(((`cal`.`timeframe_id` * 100000) + `pt_pi`.`ID_base`) + `cal`.`recurrence_index`) AS `perioditem_id`,'period_group_ID' AS `meta_key`,(`cal`.`period_group_id` + `pt_pg`.`ID_base`) AS `meta_value` from ((`wp_cb2_view_perioditem_entities` `cal` join `wp_cb2_post_types` `pt_pi` on((`pt_pi`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) join `wp_cb2_post_types` `pt_pg` on((`pt_pg`.`post_type` = 'periodgroup'))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * 100000) + `cal`.`recurrence_index`) * 10) + `pt_pi`.`ID_base`) + 5) AS `meta_id`,(((`cal`.`timeframe_id` * 100000) + `pt_pi`.`ID_base`) + `cal`.`recurrence_index`) AS `post_id`,(((`cal`.`timeframe_id` * 100000) + `pt_pi`.`ID_base`) + `cal`.`recurrence_index`) AS `perioditem_id`,'period_ID' AS `meta_key`,(`cal`.`period_id` + `pt_p`.`ID_base`) AS `meta_value` from ((`wp_cb2_view_perioditem_entities` `cal` join `wp_cb2_post_types` `pt_pi` on((`pt_pi`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) join `wp_cb2_post_types` `pt_p` on((`pt_p`.`post_type` = 'period'))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * 100000) + `cal`.`recurrence_index`) * 10) + `pt`.`ID_base`) + 6) AS `meta_id`,(((`cal`.`timeframe_id` * 100000) + `pt`.`ID_base`) + `cal`.`recurrence_index`) AS `post_id`,(((`cal`.`timeframe_id` * 100000) + `pt`.`ID_base`) + `cal`.`recurrence_index`) AS `perioditem_id`,'period_status_type_id' AS `meta_key`,`cal`.`period_status_type_id` AS `meta_value` from (`wp_cb2_view_perioditem_entities` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * 100000) + `cal`.`recurrence_index`) * 10) + `pt`.`ID_base`) + 7) AS `meta_id`,(((`cal`.`timeframe_id` * 100000) + `pt`.`ID_base`) + `cal`.`recurrence_index`) AS `post_id`,(((`cal`.`timeframe_id` * 100000) + `pt`.`ID_base`) + `cal`.`recurrence_index`) AS `perioditem_id`,'recurrence_index' AS `meta_key`,`cal`.`recurrence_index` AS `meta_value` from (`wp_cb2_view_perioditem_entities` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * 100000) + `cal`.`recurrence_index`) * 10) + `pt`.`ID_base`) + 8) AS `meta_id`,(((`cal`.`timeframe_id` * 100000) + `pt`.`ID_base`) + `cal`.`recurrence_index`) AS `post_id`,(((`cal`.`timeframe_id` * 100000) + `pt`.`ID_base`) + `cal`.`recurrence_index`) AS `perioditem_id`,'period_group_type' AS `meta_key`,`cal`.`period_group_type` AS `meta_value` from (`wp_cb2_view_perioditem_entities` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) union all select `cal`.`timeframe_id` AS `timeframe_id`,`cal`.`recurrence_index` AS `recurrence_index`,(((((`cal`.`timeframe_id` * 100000) + `cal`.`recurrence_index`) * 10) + `pt`.`ID_base`) + 9) AS `meta_id`,(((`cal`.`timeframe_id` * 100000) + `pt`.`ID_base`) + `cal`.`recurrence_index`) AS `post_id`,(((`cal`.`timeframe_id` * 100000) + `pt`.`ID_base`) + `cal`.`recurrence_index`) AS `perioditem_id`,'period_status_type_name' AS `meta_key`,`pst`.`name` AS `meta_value` from ((`wp_cb2_view_perioditem_entities` `cal` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = concat('perioditem-',`cal`.`period_group_type`)))) join `wp_cb2_period_status_types` `pst` on((`cal`.`period_status_type_id` = `pst`.`period_status_type_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `wp_cb2_view_perioditems`
--

/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_perioditems`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;



/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `wp_cb2_view_perioditems` AS select `pr`.`period_id` AS `period_id`,0 AS `recurrence_index`,`pr`.`datetime_part_period_start` AS `datetime_period_item_start`,`pr`.`datetime_part_period_end` AS `datetime_period_item_end` from `wp_cb2_periods` `pr` where (isnull(`pr`.`recurrence_type`) and (`pr`.`datetime_from` <= `pr`.`datetime_part_period_start`) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= `pr`.`datetime_part_period_end`))) union all select `pr`.`period_id` AS `period_id`,`sq`.`num` AS `recurrence_index`,(`pr`.`datetime_part_period_start` + interval `sq`.`num` year) AS `datetime_period_item_start`,(`pr`.`datetime_part_period_end` + interval `sq`.`num` year) AS `datetime_period_item_end` from (`wp_cb2_view_sequence_date` `sq` join `wp_cb2_periods` `pr`) where ((`pr`.`recurrence_type` = 'Y') and (`pr`.`datetime_from` <= (`pr`.`datetime_part_period_start` + interval `sq`.`num` year)) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= (`pr`.`datetime_part_period_end` + interval `sq`.`num` year)))) union all select `pr`.`period_id` AS `period_id`,`sq`.`num` AS `recurrence_index`,(`pr`.`datetime_part_period_start` + interval `sq`.`num` month) AS `datetime_period_item_start`,(`pr`.`datetime_part_period_end` + interval `sq`.`num` month) AS `datetime_period_item_end` from (`wp_cb2_view_sequence_date` `sq` join `wp_cb2_periods` `pr`) where ((`pr`.`recurrence_type` = 'M') and (isnull(`pr`.`recurrence_sequence`) or (`pr`.`recurrence_sequence` & (pow(2,month((`pr`.`datetime_part_period_start` + interval `sq`.`num` month))) - 1))) and (`pr`.`datetime_from` <= (`pr`.`datetime_part_period_start` + interval `sq`.`num` month)) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= (`pr`.`datetime_part_period_end` + interval `sq`.`num` month)))) union all select `pr`.`period_id` AS `period_id`,`sq`.`num` AS `recurrence_index`,(`pr`.`datetime_part_period_start` + interval `sq`.`num` week) AS `datetime_period_item_start`,(`pr`.`datetime_part_period_end` + interval `sq`.`num` week) AS `datetime_period_item_end` from (`wp_cb2_view_sequence_date` `sq` join `wp_cb2_periods` `pr`) where ((`pr`.`recurrence_type` = 'W') and (`pr`.`datetime_from` <= (`pr`.`datetime_part_period_start` + interval `sq`.`num` week)) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= (`pr`.`datetime_part_period_end` + interval `sq`.`num` week)))) union all select `pr`.`period_id` AS `period_id`,`sq`.`num` AS `recurrence_index`,(`pr`.`datetime_part_period_start` + interval `sq`.`num` day) AS `datetime_period_item_start`,(`pr`.`datetime_part_period_end` + interval `sq`.`num` day) AS `datetime_period_item_end` from (`wp_cb2_view_sequence_date` `sq` join `wp_cb2_periods` `pr`) where ((`pr`.`recurrence_type` = 'D') and (isnull(`pr`.`recurrence_sequence`) or (`pr`.`recurrence_sequence` & pow(2,(dayofweek((`pr`.`datetime_part_period_start` + interval `sq`.`num` day)) - 1)))) and (`pr`.`datetime_from` <= (`pr`.`datetime_part_period_start` + interval `sq`.`num` day)) and (isnull(`pr`.`datetime_to`) or (`pr`.`datetime_to` >= (`pr`.`datetime_part_period_end` + interval `sq`.`num` day)))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `wp_cb2_view_periodmeta`
--

/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_periodmeta`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;



/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `wp_cb2_view_periodmeta` AS select ((`p`.`period_id` * 10) + `pt`.`ID_base`) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'period_id' AS `meta_key`,`p`.`period_id` AS `meta_value` from (`wp_cb2_view_period_posts` `p` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) union all select (((`p`.`period_id` * 10) + `pt`.`ID_base`) + 1) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'datetime_part_period_start' AS `meta_key`,`p`.`datetime_part_period_start` AS `meta_value` from (`wp_cb2_view_period_posts` `p` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) union all select (((`p`.`period_id` * 10) + `pt`.`ID_base`) + 2) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'datetime_part_period_end' AS `meta_key`,`p`.`datetime_part_period_end` AS `meta_value` from (`wp_cb2_view_period_posts` `p` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) union all select (((`p`.`period_id` * 10) + `pt`.`ID_base`) + 3) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'datetime_from' AS `meta_key`,`p`.`datetime_from` AS `meta_value` from (`wp_cb2_view_period_posts` `p` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) union all select (((`p`.`period_id` * 10) + `pt`.`ID_base`) + 4) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'datetime_to' AS `meta_key`,`p`.`datetime_to` AS `meta_value` from (`wp_cb2_view_period_posts` `p` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) union all select (((`p`.`period_id` * 10) + `pt`.`ID_base`) + 5) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'recurrence_type' AS `meta_key`,`p`.`recurrence_type` AS `meta_value` from (`wp_cb2_view_period_posts` `p` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) union all select (((`p`.`period_id` * 10) + `pt`.`ID_base`) + 6) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'recurrence_frequency' AS `meta_key`,`p`.`recurrence_frequency` AS `meta_value` from (`wp_cb2_view_period_posts` `p` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) union all select (((`p`.`period_id` * 10) + `pt`.`ID_base`) + 7) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'recurrence_sequence' AS `meta_key`,cast(`p`.`recurrence_sequence` as unsigned) AS `meta_value` from (`wp_cb2_view_period_posts` `p` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) union all select (((`p`.`period_id` * 10) + `pt`.`ID_base`) + 8) AS `meta_id`,`p`.`ID` AS `post_id`,`p`.`ID` AS `period_id`,'period_group_IDs' AS `meta_key`,`p`.`period_group_IDs` AS `meta_value` from (`wp_cb2_view_period_posts` `p` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `p`.`post_type`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `wp_cb2_view_periodstatustype_posts`
--

/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_periodstatustype_posts`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;



/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `wp_cb2_view_periodstatustype_posts` AS select (`p`.`period_status_type_id` + `pt`.`ID_base`) AS `ID`,1 AS `post_author`,'2018-01-01' AS `post_date`,'2018-01-01' AS `post_date_gmt`,`p`.`description` AS `post_content`,`p`.`name` AS `post_title`,'description' AS `post_excerpt`,'publish' AS `post_status`,'closed' AS `comment_status`,'closed' AS `ping_status`,'' AS `post_password`,`p`.`name` AS `post_name`,'' AS `to_ping`,'' AS `pinged`,'2018-01-01' AS `post_modified`,'2018-01-01' AS `post_modified_gmt`,'' AS `post_content_filtered`,0 AS `post_parent`,'' AS `guid`,0 AS `menu_order`,'periodstatustype' AS `post_type`,'' AS `post_mime_type`,0 AS `comment_count`,`p`.`period_status_type_id` AS `period_status_type_id`,`p`.`name` AS `name`,`p`.`description` AS `description`,`p`.`flags` AS `flags`,`p`.`colour` AS `colour`,`p`.`opacity` AS `opacity`,`p`.`priority` AS `priority`,(`p`.`period_status_type_id` <= 6) AS `system` from (`wp_cb2_period_status_types` `p` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = 'periodstatustype'))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `wp_cb2_view_periodstatustypemeta`
--

/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_periodstatustypemeta`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;



/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `wp_cb2_view_periodstatustypemeta` AS select ((`pst`.`period_status_type_id` * 10) + `pt`.`ID_base`) AS `meta_id`,`pst`.`ID` AS `post_id`,`pst`.`ID` AS `periodstatustype_id`,'flags' AS `meta_key`,cast(`pst`.`flags` as unsigned) AS `meta_value` from (`wp_cb2_view_periodstatustype_posts` `pst` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `pst`.`post_type`))) union all select (((`pst`.`period_status_type_id` * 10) + `pt`.`ID_base`) + 1) AS `meta_id`,`pst`.`ID` AS `post_id`,`pst`.`ID` AS `periodstatustype_id`,'colour' AS `meta_key`,`pst`.`colour` AS `meta_value` from (`wp_cb2_view_periodstatustype_posts` `pst` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `pst`.`post_type`))) union all select (((`pst`.`period_status_type_id` * 10) + `pt`.`ID_base`) + 2) AS `meta_id`,`pst`.`ID` AS `post_id`,`pst`.`ID` AS `periodstatustype_id`,'opacity' AS `meta_key`,`pst`.`opacity` AS `meta_value` from (`wp_cb2_view_periodstatustype_posts` `pst` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `pst`.`post_type`))) union all select (((`pst`.`period_status_type_id` * 10) + `pt`.`ID_base`) + 3) AS `meta_id`,`pst`.`ID` AS `post_id`,`pst`.`ID` AS `periodstatustype_id`,'priority' AS `meta_key`,`pst`.`priority` AS `meta_value` from (`wp_cb2_view_periodstatustype_posts` `pst` join `wp_cb2_post_types` `pt` on((`pt`.`post_type` = `pst`.`post_type`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `wp_cb2_view_sequence_date`
--

/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_sequence_date`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;



/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `wp_cb2_view_sequence_date` AS select (((`t4`.`num` * 1000) + (`t3`.`num` * 100)) + ((`t2`.`num` * 10) + `t1`.`num`)) AS `num`,(cast('2018-07-01' as datetime) + interval ((((`t4`.`num` * 1000) + (`t3`.`num` * 100)) + (`t2`.`num` * 10)) + `t1`.`num`) day) AS `datetime_start`,(cast('2018-07-01' as datetime) + interval (((((`t4`.`num` * 1000) + (`t3`.`num` * 100)) + (`t2`.`num` * 10)) + `t1`.`num`) + 1) day) AS `datetime_end` from (((`wp_cb2_view_sequence_num` `t1` join `wp_cb2_view_sequence_num` `t2`) join `wp_cb2_view_sequence_num` `t3`) join `wp_cb2_view_sequence_num` `t4`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `wp_cb2_view_sequence_num`
--

/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_sequence_num`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;



/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `wp_cb2_view_sequence_num` AS select 0 AS `num` union all select 1 AS `1` union all select 2 AS `2` union all select 3 AS `3` union all select 4 AS `4` union all select 5 AS `5` union all select 6 AS `6` union all select 7 AS `7` union all select 8 AS `8` union all select 9 AS `9` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `wp_cb2_view_timeframe_options`
--

/*!50001 DROP VIEW IF EXISTS `wp_cb2_view_timeframe_options`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;



/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `wp_cb2_view_timeframe_options` AS select distinct `c2to`.`timeframe_id` AS `timeframe_id`,(select `wp_cb2_timeframe_options`.`option_value` from `wp_cb2_timeframe_options` where ((`wp_cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`wp_cb2_timeframe_options`.`option_name` = 'max-slots')) order by `wp_cb2_timeframe_options`.`option_id` desc limit 1) AS `max-slots`,(select `wp_cb2_timeframe_options`.`option_value` from `wp_cb2_timeframe_options` where ((`wp_cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`wp_cb2_timeframe_options`.`option_name` = 'closed-days-booking')) order by `wp_cb2_timeframe_options`.`option_id` desc limit 1) AS `closed-days-booking`,(select `wp_cb2_timeframe_options`.`option_value` from `wp_cb2_timeframe_options` where ((`wp_cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`wp_cb2_timeframe_options`.`option_name` = 'consequtive-slots')) order by `wp_cb2_timeframe_options`.`option_id` desc limit 1) AS `consequtive-slots`,(select `wp_cb2_timeframe_options`.`option_value` from `wp_cb2_timeframe_options` where ((`wp_cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`wp_cb2_timeframe_options`.`option_name` = 'use-codes')) order by `wp_cb2_timeframe_options`.`option_id` desc limit 1) AS `use-codes`,(select `wp_cb2_timeframe_options`.`option_value` from `wp_cb2_timeframe_options` where ((`wp_cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`wp_cb2_timeframe_options`.`option_name` = 'limit')) order by `wp_cb2_timeframe_options`.`option_id` desc limit 1) AS `limit`,(select `wp_cb2_timeframe_options`.`option_value` from `wp_cb2_timeframe_options` where ((`wp_cb2_timeframe_options`.`timeframe_id` = `c2to`.`timeframe_id`) and (`wp_cb2_timeframe_options`.`option_name` = 'holiday_provider')) order by `wp_cb2_timeframe_options`.`option_id` desc limit 1) AS `holiday-provider` from `wp_cb2_timeframe_options` `c2to` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-09-04 10:53:46
