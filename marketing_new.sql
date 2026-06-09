-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 09, 2026 at 09:14 AM
-- Server version: 10.6.11-MariaDB
-- PHP Version: 8.1.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `marketing_new`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`cloud`@`%` PROCEDURE `DeleteLeadRelatedData` (IN `p_email` VARCHAR(150), IN `p_phone` VARCHAR(20), IN `p_name` VARCHAR(150))   BEGIN
    DECLARE v_lead_id INT;
    DECLARE done INT DEFAULT 0;
    DECLARE v_quotation_id INT;

    -- Cursor for quotation IDs
    DECLARE quotation_cursor CURSOR FOR
        SELECT id
        FROM quotations
        WHERE client_name = p_name;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    -- Get lead ID
    SELECT id INTO v_lead_id
    FROM leads
    WHERE email = p_email
      AND phone_number = p_phone
      AND full_name = p_name
    LIMIT 1;

    -- If lead exists
    IF v_lead_id IS NOT NULL THEN

        -- Delete activation links by lead
        DELETE FROM cpo_activation_links
        WHERE lead_id = v_lead_id;

        -- Open quotation cursor
        OPEN quotation_cursor;

        quotation_loop: LOOP

            FETCH quotation_cursor INTO v_quotation_id;

            IF done = 1 THEN
                LEAVE quotation_loop;
            END IF;

            -- Delete related records
            DELETE FROM bank_details
            WHERE quotation_id = v_quotation_id;

            DELETE FROM productss
            WHERE quotation_id = v_quotation_id;

            DELETE FROM summary
            WHERE quotation_id = v_quotation_id;

            DELETE FROM cpo_activation_links
            WHERE quotation_id = v_quotation_id;

            DELETE FROM payments
            WHERE related_id = v_quotation_id;

            -- Delete quotation
            DELETE FROM quotations
            WHERE id = v_quotation_id;

        END LOOP;

        CLOSE quotation_cursor;

        -- Finally delete lead
        DELETE FROM leads
        WHERE id = v_lead_id;

    END IF;

END$$

CREATE DEFINER=`cloud`@`%` PROCEDURE `DeleteQuotationPayments` (IN `p_quotation_no` VARCHAR(50))   BEGIN
    DECLARE v_quotation_id INT;

    -- Get quotation ID
    SELECT id INTO v_quotation_id
    FROM quotations
    WHERE quotation_no = p_quotation_no
    LIMIT 1;

    -- If quotation exists
    IF v_quotation_id IS NOT NULL THEN

        -- Delete related payments
        DELETE FROM payments
        WHERE related_id = v_quotation_id;

        -- Update quotation order status
        UPDATE quotations
        SET order_status = 'N'
        WHERE id = v_quotation_id;

        -- Update summary payment status
        UPDATE summary
        SET payment_status = 'N'
        WHERE quotation_id = v_quotation_id;

    END IF;

END$$

CREATE DEFINER=`cloud`@`%` PROCEDURE `DeleteQuotationRelatedData` (IN `p_quotation_no` VARCHAR(50))   BEGIN
    DECLARE v_quotation_id INT;

    -- Get quotation ID
    SELECT id INTO v_quotation_id
    FROM quotations
    WHERE quotation_no = p_quotation_no
    LIMIT 1;

    -- If quotation exists
    IF v_quotation_id IS NOT NULL THEN

        -- Delete related tables
        DELETE FROM bank_details
        WHERE quotation_id = v_quotation_id;

        DELETE FROM summary
        WHERE quotation_id = v_quotation_id;

        DELETE FROM productss
        WHERE quotation_id = v_quotation_id;

        DELETE FROM payments
        WHERE related_id = v_quotation_id;

        DELETE FROM cpo_activation_links
        WHERE quotation_id = v_quotation_id;

        -- Finally delete quotation
        DELETE FROM quotations
        WHERE id = v_quotation_id;

    END IF;

END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `bank_details`
--

CREATE TABLE `bank_details` (
  `id` int(11) NOT NULL,
  `quotation_id` int(11) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `branch_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `bank_details`
--

INSERT INTO `bank_details` (`id`, `quotation_id`, `bank_name`, `account_number`, `ifsc_code`, `branch_name`) VALUES
(1, 1, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(2, 2, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(3, 3, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(4, 4, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(5, 5, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(6, 6, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(7, 7, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(10, 10, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(11, 11, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(12, 12, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(13, 13, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(14, 14, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(15, 15, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(16, 16, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(19, 19, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(27, 27, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(28, 28, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(35, 35, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(39, 39, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(42, 42, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(43, 43, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(44, 44, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(72, 72, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(73, 73, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(77, 77, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(78, 78, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(79, 79, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(80, 80, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(81, 81, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(82, 82, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(83, 83, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(84, 84, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(85, 85, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(86, 86, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(87, 87, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(88, 88, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(89, 89, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(90, 90, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(91, 91, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(92, 92, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(93, 93, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(95, 95, 'HDFC Bank', '601305021603', 'ICIC0006013', 'MADURAI SUBRAMANIPURAM BRANCH'),
(96, 96, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(102, 102, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(107, 107, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(108, 108, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(109, 109, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(110, 110, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(111, 111, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(112, 112, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(113, 113, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(114, 114, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(115, 115, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(116, 116, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(117, 117, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(119, 119, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(120, 120, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(121, 121, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(122, 122, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(123, 123, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(124, 124, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(125, 125, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(126, 126, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(127, 127, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(128, 128, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(130, 130, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(131, 131, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(132, 132, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(133, 133, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(134, 134, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(135, 135, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(136, 136, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(137, 137, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(138, 138, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(139, 139, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(140, 140, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(141, 141, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(142, 142, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(144, 144, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(145, 145, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(146, 146, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(148, 148, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(149, 149, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(150, 150, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(151, 151, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(155, 155, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(164, 164, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(165, 165, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(167, 167, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(168, 168, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(169, 169, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(172, 172, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(173, 173, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(174, 174, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(175, 175, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(176, 176, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(177, 177, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(178, 178, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(179, 179, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(180, 180, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(181, 181, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(182, 182, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(183, 183, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(184, 184, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(185, 185, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(186, 186, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai'),
(187, 187, 'Bank of Baroda', '97400500000298', 'BARB0DBMRAI', 'Kamarajar Salai');

-- --------------------------------------------------------

--
-- Table structure for table `cpo_activation_links`
--

CREATE TABLE `cpo_activation_links` (
  `id` int(11) NOT NULL,
  `quotation_id` int(1) DEFAULT NULL,
  `lead_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `cpo_link` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `cpo_activation_links`
--

INSERT INTO `cpo_activation_links` (`id`, `quotation_id`, `lead_id`, `token`, `is_used`, `used_at`, `created_at`, `cpo_link`) VALUES
(1, 16, 8, 'dd226dd30de6729355cf8c358d4d72750a370c832759a3ee583c786fcf08dab5', 1, NULL, '2026-03-09 12:34:19', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=dd226dd30de6729355cf8c358d4d72750a370c832759a3ee583c786fcf08dab5'),
(2, 17, 11, 'b9b0f6958e98d8b1f057142a720ee796898efa71358ed9a9942aa36729a97b33', 0, NULL, '2026-03-13 04:04:24', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=b9b0f6958e98d8b1f057142a720ee796898efa71358ed9a9942aa36729a97b33'),
(3, 18, 12, '62c19174f557faf6b4b795f2bedd0768bcbe5e58e90012a89b094297b207f62a', 1, NULL, '2026-03-13 04:21:44', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=62c19174f557faf6b4b795f2bedd0768bcbe5e58e90012a89b094297b207f62a'),
(4, 20, 14, '80f7b78bb7b9bacace8b04f13d9dc6b14aa0135f5c1c60ddd49d8b714afc60cb', 0, NULL, '2026-03-14 10:13:18', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=80f7b78bb7b9bacace8b04f13d9dc6b14aa0135f5c1c60ddd49d8b714afc60cb'),
(5, 21, 15, '252b9f7986f0108b54bfcad0909e8d3625da70bfd263debe27129f2ffded4bed', 0, NULL, '2026-03-14 10:26:07', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=252b9f7986f0108b54bfcad0909e8d3625da70bfd263debe27129f2ffded4bed'),
(6, 22, 16, '2038f6260c9a50715bd44771332524c21b8bf7894dd0c4dbc90f265dc5876546', 0, NULL, '2026-03-14 10:59:34', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=2038f6260c9a50715bd44771332524c21b8bf7894dd0c4dbc90f265dc5876546'),
(7, 23, 17, 'd114b2556e58aa0c0d9007536e6b19a9653b4e616159e5aac12f05bd99468aab', 0, NULL, '2026-03-14 12:20:41', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=d114b2556e58aa0c0d9007536e6b19a9653b4e616159e5aac12f05bd99468aab'),
(8, 24, 18, '57b9b80e104434aab7edd8cc41b6d54493aab8ea6bdb8fbe8b4dbf0759435393', 0, NULL, '2026-03-16 05:22:08', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=57b9b80e104434aab7edd8cc41b6d54493aab8ea6bdb8fbe8b4dbf0759435393'),
(9, 25, 20, '9b0c323fe5bf070a9c93aa3902f30047b964407807f34f2c39f20df0a7a08f40', 0, NULL, '2026-03-16 06:05:55', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=9b0c323fe5bf070a9c93aa3902f30047b964407807f34f2c39f20df0a7a08f40'),
(10, 26, 21, '0943cb66474080b8fc6dbe6e67788ba9fb7a9a0355e409a06f36323571ebb168', 0, NULL, '2026-03-16 06:09:25', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=0943cb66474080b8fc6dbe6e67788ba9fb7a9a0355e409a06f36323571ebb168'),
(11, 29, 23, 'ecb2ee21f710e9dd6bce4c4dff94cde5ad24250215b7ac6a66e11741dc515f59', 0, NULL, '2026-03-17 10:08:37', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=ecb2ee21f710e9dd6bce4c4dff94cde5ad24250215b7ac6a66e11741dc515f59'),
(12, 30, 23, '9b87f5b88309b6ce25702e51d911793b7a32f7fe67d27e48a71eac8b3cc49e35', 0, NULL, '2026-03-17 11:12:06', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=9b87f5b88309b6ce25702e51d911793b7a32f7fe67d27e48a71eac8b3cc49e35'),
(13, 31, 24, '39a86afa228ce4cbc00a8c4e14eeb0d2c9ac8485491982ac76d4f78ce08d146e', 0, NULL, '2026-03-17 11:24:25', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=39a86afa228ce4cbc00a8c4e14eeb0d2c9ac8485491982ac76d4f78ce08d146e'),
(45, 81, 65, '688525c82b5d50153b2118187bee70d4c84de6992bee905dbef8ca7b9d798864', 0, NULL, '2026-04-06 10:29:36', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=688525c82b5d50153b2118187bee70d4c84de6992bee905dbef8ca7b9d798864'),
(46, 80, 61, '6f0dfec4d227e51cc4438ecb09c1f9105d556b1a609ad026afe815cf476df7cb', 1, NULL, '2026-04-18 10:24:50', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=6f0dfec4d227e51cc4438ecb09c1f9105d556b1a609ad026afe815cf476df7cb'),
(47, 88, 76, '8f1ab0aeadbff2bf89b9fbd6d7143c6bcb3d1f79c47ca1ab8d796ee70701bdae', 1, NULL, '2026-04-18 11:46:38', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=8f1ab0aeadbff2bf89b9fbd6d7143c6bcb3d1f79c47ca1ab8d796ee70701bdae'),
(48, 87, 75, '0c08ab8d213b4d803a5bfa7ce8a4a77b5ce87a0d1b0a8453618fd038fc376050', 0, NULL, '2026-05-05 05:16:49', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=0c08ab8d213b4d803a5bfa7ce8a4a77b5ce87a0d1b0a8453618fd038fc376050'),
(49, 96, 114, '38e3187218a50cd4fb4b4accd8c0f05615f4f6e5d73a26a0427958e1945d0ebe', 0, NULL, '2026-04-22 06:20:54', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=38e3187218a50cd4fb4b4accd8c0f05615f4f6e5d73a26a0427958e1945d0ebe'),
(53, 104, 147, 'e06168c3071e5834e9c613bbfee2f8b745c83c46c82bd5bac77549948dddab14', 0, NULL, '2026-04-25 10:09:19', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=e06168c3071e5834e9c613bbfee2f8b745c83c46c82bd5bac77549948dddab14'),
(55, 102, 146, '618fe6559faba6eb0a95e02c29bf2dae80c4dbbadc896344717178bc9a49faf5', 0, NULL, '2026-05-06 07:49:20', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=618fe6559faba6eb0a95e02c29bf2dae80c4dbbadc896344717178bc9a49faf5'),
(58, 138, 169, 'f5f9af15c4bd9a976f685e207cabc0427d2363e305afff39a9b5956a93e0bcd5', 0, NULL, '2026-05-13 08:27:12', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=f5f9af15c4bd9a976f685e207cabc0427d2363e305afff39a9b5956a93e0bcd5'),
(72, 132, 87, 'e7bc5c95453ae4614460a231235245a76643072657c72ad433cf55d52afccc6a', 0, NULL, '2026-05-20 05:00:24', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=e7bc5c95453ae4614460a231235245a76643072657c72ad433cf55d52afccc6a&lead_id=87'),
(77, 175, 150, 'a5bf28eaf0a3e545ea5c86cafef78f1552d0810be52241a70f3b02efb59f0815', 0, NULL, '2026-05-30 05:07:16', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=a5bf28eaf0a3e545ea5c86cafef78f1552d0810be52241a70f3b02efb59f0815&lead_id=150'),
(78, 179, 116, 'bf185044cf96c54c49b617768ca965a08480190cac933e03875cd42ca8299e21', 0, NULL, '2026-06-03 08:11:48', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=bf185044cf96c54c49b617768ca965a08480190cac933e03875cd42ca8299e21&lead_id=116'),
(79, 180, 186, 'e63cc3029e7a2f860b18ff6e429a29b1f0eac59ed2c148409eba4ea2938d7fb8', 0, NULL, '2026-06-09 05:59:13', 'https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=e63cc3029e7a2f860b18ff6e429a29b1f0eac59ed2c148409eba4ea2938d7fb8&lead_id=186'),
(80, 186, 113, 'a0b10c0a44c67f91fa2bc42da89cc18dc0c65329f71bc01217e24267cdf58e6b', 0, NULL, '2026-06-09 06:35:22', 'https://star.tuckermotors.com/Home_app/partner/fcg.php?token=a0b10c0a44c67f91fa2bc42da89cc18dc0c65329f71bc01217e24267cdf58e6b&lead_id=113');

-- --------------------------------------------------------

--
-- Table structure for table `customer_types`
--

CREATE TABLE `customer_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `cms_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `customer_types`
--

INSERT INTO `customer_types` (`id`, `type_name`, `cms_id`) VALUES
(1, 'End User', '8'),
(2, 'Apartment', '8'),
(3, 'Corporate Office', '8'),
(4, 'Dealer', '8'),
(5, 'White label', '5'),
(6, 'Wurth', '3'),
(7, 'Rebolt', '7');

-- --------------------------------------------------------

--
-- Table structure for table `customer_types_old`
--

CREATE TABLE `customer_types_old` (
  `id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `customer_types_old`
--

INSERT INTO `customer_types_old` (`id`, `type_name`) VALUES
(1, 'End User'),
(2, 'Reseller'),
(3, 'Distributor'),
(4, 'OEM'),
(5, 'Corporate');

-- --------------------------------------------------------

--
-- Table structure for table `dealer_details`
--

CREATE TABLE `dealer_details` (
  `id` int(11) NOT NULL,
  `quotation_id` int(1) DEFAULT NULL,
  `lead_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `mobile_no` varchar(10) DEFAULT NULL,
  `mail_id` varchar(100) DEFAULT NULL,
  `dealer_status` char(1) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `is_verified` tinyint(4) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `franchise_messages`
--

CREATE TABLE `franchise_messages` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inquiries_messages`
--

CREATE TABLE `inquiries_messages` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `inquiries_messages`
--

INSERT INTO `inquiries_messages` (`id`, `lead_id`, `message`, `created_at`) VALUES
(1, 83, 'Spoke to her', '2026-04-02 04:46:15'),
(2, 83, 'Details shared to her', '2026-04-02 04:46:33');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_summary`
--

CREATE TABLE `inventory_summary` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `cpo_id` varchar(100) DEFAULT NULL,
  `invoice_id` varchar(50) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL,
  `net_value` decimal(12,2) NOT NULL,
  `total_discount` decimal(12,2) DEFAULT NULL,
  `gst_value` decimal(12,2) DEFAULT NULL,
  `grand_total` decimal(12,2) DEFAULT NULL,
  `payment_status` char(1) DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `inventory_summary`
--

INSERT INTO `inventory_summary` (`id`, `lead_id`, `cpo_id`, `invoice_id`, `subtotal`, `net_value`, `total_discount`, `gst_value`, `grand_total`, `payment_status`) VALUES
(1, 71, 'TTA-AA41', 'INV-015', '300066.00', '354077.88', '0.00', '54011.88', '354077.88', 'N'),
(2, 107, 'TTN-AA30', 'INV-014', '300099.00', '354116.82', '0.00', '54017.82', '354116.82', 'N'),
(5, 107, 'TTN-AA30', 'INV-017', '300000.00', '354000.00', '0.00', '54000.00', '354000.00', 'N'),
(6, 107, 'TTN-AA30', 'INV-018', '300000.00', '354000.00', '0.00', '54000.00', '354000.00', 'N'),
(7, 171, 'TTA-AA46', '0', '2.00', '2.36', '0.00', '0.36', '2.36', 'N'),
(8, 77, 'PKL-AA21', '0', '300000.00', '354000.00', '0.00', '54000.00', '354000.00', 'N'),
(9, 77, 'PKL-AA21', '0', '12000.00', '14160.00', '0.00', '2160.00', '14160.00', 'N');

-- --------------------------------------------------------

--
-- Table structure for table `inventry_payments`
--

CREATE TABLE `inventry_payments` (
  `id` int(11) NOT NULL,
  `cpo_id` varchar(20) DEFAULT NULL,
  `invoice_id` varchar(50) DEFAULT NULL,
  `payment_mode` varchar(50) DEFAULT NULL,
  `total_amount` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `proof_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `inventry_payments`
--

INSERT INTO `inventry_payments` (`id`, `cpo_id`, `invoice_id`, `payment_mode`, `total_amount`, `amount`, `payment_reference`, `proof_file`, `created_at`) VALUES
(1, 'TTN-AA30', 'INV-015', 'Cash', '300066.00', '1000.00', 'order_SR4IwakqepKZ9Q', NULL, '2026-04-22 11:40:39');

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(11) NOT NULL,
  `parent_id` int(1) DEFAULT NULL,
  `salutation` varchar(50) DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `pincode` int(11) NOT NULL,
  `customer_type_id` int(11) DEFAULT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `gst_number` varchar(50) DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `cpo_id` varchar(50) DEFAULT NULL,
  `cms_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `parent_id`, `salutation`, `full_name`, `email`, `phone_number`, `address`, `address2`, `city`, `state`, `pincode`, `customer_type_id`, `company_name`, `gst_number`, `status_id`, `source_id`, `notes`, `created_at`, `cpo_id`, `cms_id`) VALUES
(1, 5, 'Mrs.', 'Teja Sahithi', 'teja.sahithi@eorionev.com', '7842488853', '21-160, Swatantra Nagar, Madhurawada,', 'Vishakapatnam', 'Visakapatinam', 'AndhraPradesh', 530048, 2, 'Eorion Technologies Private Limited', '', 1, 6, '', '2026-02-02 07:17:29', '0', '8'),
(3, 5, 'Mr.', 'Saranga Raja', 'rajasaranga@gmail.com', '9944193873', '168/53, Kuthukalavalasai, ChithraNagar,', '', 'Tenkasi', 'Tamilnadu', 627803, 1, 'M/S. Sabarish Motors', '', 1, 3, '', '2026-02-04 11:30:16', '0', '8'),
(4, 5, 'Mr.', 'Sanjai Prasath', '', '8754934151', '38D, Perasiriyar Colony,Perianaicken Palayam,', '', 'Coimbatore', 'TamilNadu', 641020, 1, '', '', 1, 1, '', '2026-02-05 04:39:12', '0', '8'),
(5, 5, 'Mr.', 'Amal', 'info@pappasheritage.com', '9037562315', 'Ward No.15, Kuttipuzha building No.: 540, Door. no.7, P.O -Adimali', '', 'Adimali', 'Kerala', 685561, 1, 'Pappa\'s Heritage', '32BZEPP4812R1ZY', 1, 7, '', '2026-02-05 05:13:07', '0', '8'),
(6, 5, 'Mr.', 'Varsha', '', '7898860305', '9A/B, SECTOR-H, GOVINDAPURA INDUSTRIAL AREA', '', 'Bhopal', 'Madhya Pradesh', 462023, 5, 'COSMOS SYSTEM INTEGRATORS PRIVATE LIMITED', '23AAFCC9978B1ZK', 1, 1, '', '2026-02-24 04:25:07', 'TMA-AA37', '7'),
(7, 5, 'Mr.', 'Magesh', 'magesh.g@vsolarindia.com', '7356601633', 'RVR Complex, DasanikenPatti Village,', '', 'Salem', 'TamilNadu', 636201, 5, 'Virgin Power & Engineering Pvt Ltd', '', 1, 6, '', '2026-03-02 08:23:56', 'TTA-AA38', '7'),
(9, 5, 'Mr.', 'Shahid', '', '9249502474', 'Ground Floor, Fathima Building, Thrissur - Kunnamkulam Rd, near Federal Bank, Kechery', '', 'Thrissur', 'Kerala', 680501, 5, 'Meeker India Energies Pvt Ltd', '', 1, 2, '', '2026-03-09 08:02:45', 'TKE-AA38', '7'),
(13, 5, 'Mr.', 'Laxmi Narayanan', '', '9094128142', 'Ground Floor No. 9, Madhavi Street, Karukku Main Road, Chennai', '', 'Chennai', 'TamilNadu', 600053, 1, '', '', 1, 7, '', '2026-03-13 06:36:24', 'TTA-AA40', '8'),
(19, 5, 'Mr.', 'Subramanian Chokkalingam', '', '9865732111', '39/1, Muthuramalinga Street', 'Arasaradi', 'Madurai', 'Tamilnadu', 625003, 1, 'Kalam\'sGreen Energy', '', 1, 7, '', '2026-03-16 05:31:53', 'TTA-AA40', '8'),
(22, 5, 'Mr.', 'Pandiyan Chinnasamy', '', '9443676295', '2538,TNHB Kudiyiruppu, Villapuram, Avaniyapuram', '', 'Madurai', 'TamilNadu', 625012, 1, '', '', 1, 6, '', '2026-03-17 05:27:03', 'TTA-AA40', '8'),
(28, 5, 'Mr.', 'Jishnu Vijayan', '', '9600318534', 'First Floor, RTBI, E Block, IITM RESEARCH PARK, Kanagam Rd, Kanagam, Tharamani', '', 'Chennai', 'TamilNadu', 600113, 1, 'PlugZmart', '', 1, 6, '', '2026-03-24 09:28:25', 'TTA-AA39', '8'),
(29, 5, 'Mr.', 'Sahad', '', '9567432219', 'Sudheer Manzil, Chiruvallimukku,', 'Chirayinkeezhu P.O', 'Tiruvanthapuram', 'Kerala', 695304, 1, 'Green Line', '', 1, 6, '', '2026-03-26 05:00:03', 'TKE-AA39', '8'),
(31, 5, 'Mr.', 'Vijeesh Vasu', '', '8593933300', 'Door No.2211,2/1149/I, Hilite Business Park, kozhikode,', '', 'Olavanna', 'Kerala', 673019, 4, 'E4EV Energy Storage Private Limited', '32AAHCE0407H1Z7', 1, 2, '', '2026-03-27 07:13:08', 'TKE-AA40', '8'),
(34, 5, 'Mr.', 'Vivek', 'saiaadivtrasnport@stgroups.in', '7339566868', 'Sai Aadiv Transport', '', 'Coimbatore', 'TamilNadu', 641006, 1, '', '', 1, 6, '', '2026-03-28 06:51:25', 'TTA-AA40', '8'),
(36, 5, 'Mr.', 'John Kennedy', '', '7397176407', 'Shettiyarpatti, Rajapalayam Taluk', '', 'Virudhunagar', 'TamilNadu', 626122, 1, '', '', 1, 6, '', '2026-03-28 07:19:57', 'TTA-AA40', '8'),
(61, 5, 'Mr.', 'Er. M. Joseph Rathinaswamy', 'accounts@germanushotels.com', '9842153663', '36A, West Ponnagaram, 8th Street,', '', 'Madurai', 'TamilNadu', 625016, 1, 'Hotel Germanus', '', 1, 2, '', '2026-04-01 06:04:12', 'TTN-AA42', '8'),
(62, 5, 'Mr.', 'Vidit Gupta', 'Vidit.gupta@gmcjaipur.com', '9950996150', 'Near Agarwal College,Agra Road,', '', 'Jaipur', 'Rajasthan', 302004, 1, 'Gupta Motor Company', '', 1, 6, '', '2026-04-04 08:02:45', 'TRA-AA41', '8'),
(63, 5, 'Mrs.', 'Selvi Jothi', '', '8220974814', '1/15-20, Ramachandra Nagar, Aviyur,', '', 'Virudhunagar', 'TamilNadu', 626106, 1, '', '', 1, 7, '', '2026-04-06 05:30:25', 'TTA-AA41', '8'),
(64, 5, 'Mr.', 'Syed', '', '8610179320', '2/206, West 2nd Street, Avudaiyar Kovil', '', 'Pudukottai', 'TamilNadu', 614618, 1, '', '', 1, 2, '', '2026-04-06 08:24:41', 'TTA-AA41', '8'),
(66, 5, 'Mr.', 'Ram Kumar', '', '8939901712', 'No 1, Sivaganga Rd, Veerapanjan,', '', 'Madurai', 'TamilNadu', 625020, 1, '', '', 1, 6, '', '2026-04-08 07:36:32', 'TTA-AA41', '8'),
(67, 5, 'Mr.', 'Saran', '', '9600707159', 'Vembur', '', 'Thoothukudi', 'TamilNadu', 628905, 1, '', '', 1, 6, '', '2026-04-09 08:10:35', 'TTA-AA41', '8'),
(69, 5, 'Mr.', 'V. Kannan', '', '9095552535', 'VKR Traders', '', 'Karaikudi', 'TamilNadu', 630001, 1, '', '', 1, 2, '', '2026-04-13 05:52:25', 'TTA-AA41', '8'),
(70, 5, 'Mr.', 'Shubham', '', '9777771713', 'House No.50, Ward no.2, Bada Pool pass, Nasrullaganj', '', 'Sehore', 'Madhya Pradesh', 466331, 1, 'Ambiika Tradex and Infrastructure Pvt Ltd', '', 1, 7, '', '2026-04-16 06:15:25', 'TMA-AA41', '8'),
(71, 5, 'Mr.', 'Ponvairavan', 'vasut1156@gmail.com', '9444521602', '4/629, 4 Way Road,Srinivasa colony', 'Thattanoor village', 'Madurai', 'Tamil Nadu', 625006, 1, 'Ever Power Charging Station', '33ACKPV4624K2Z7', 1, 6, '', '2026-04-17 06:22:54', 'TTA-AA41', '8'),
(72, 5, 'Mrs.', 'Nandhini Murugan', 'nandhinishri31@gmail.com', '9442774866', '238/2, Vinayagar kovil street - 10', 'Kasipalayam, Erode', 'Erode', 'Tamil Nadu', 638010, 1, 'Vishalini Enterprises', '', 1, 6, '', '2026-04-17 06:30:46', 'TTA-AA41', '8'),
(73, 5, 'Mr.', 'Arputharaj', 'iarputharaj1989@gmail.com', '9384701005', 'Perungudi road, Airport Rd', 'Mandela Nagar', 'Madurai', 'Tamil Nadu', 625022, 1, 'Jacks Charging Station', '33AYBPA0994N2ZD', 1, 6, '', '2026-04-17 06:37:15', 'TTA-AA41', '8'),
(74, 5, 'Mr.', 'Shailendra', '', '9453231900', 'Lucknow', '', 'Lucknow', 'UttarPradesh', 226023, 5, 'Plugup Green EV Charging LLP', '', 1, 6, '', '2026-04-17 08:10:37', 'TUT-AA41', '7'),
(75, 5, 'Mr.', 'Sathish kumar', 'maintenance@coralengineeringworks.com', '9384736388', 'Plot S-1/Pt.-2, SIPCOT Engineering SEZ, SIPCOT', 'Engineering SEZ Road, Perundurai Industrial Park, Perundurai', 'Erode', 'Tamil Nadu', 638052, 3, 'CORAL ENGINEERING WORKS INDIA PRIVATE LIMITED - SEZ UNIT', '33AAICC7149R2ZZ', 1, 6, '', '2026-04-18 11:08:59', 'TTA-AA41', '8'),
(76, 5, 'Mr.', 'Saravana kumar', 'saravanaram89@gmail.com', '9952271185', '5/208-B, Trichy Main Road, Marachipatti, Elurpatti  Post', 'Thottiyam', 'Tiruchirappalli', 'Tamil Nadu', 621215, 1, 'Om Saravana Oil Mill & Traders', '33DMCPS0757M1ZP', 1, 6, '', '2026-04-18 11:24:00', 'TTN-AA44', '8'),
(77, 5, 'Dear ', 'Extrawave', 'extrawave@gmail.com', '8925964348', '', '', 'Madurai', 'Tamilnadu ', 900001, 1, NULL, '', 1, 2, NULL, '2024-12-30 14:58:14', 'PKL-AA21', '8'),
(78, 5, 'Dear ', 'Jolly', 'drjollysj@rediffmail.com', '9446404527 ', '', '', 'Pathaanamthitta ', 'Kerala ', 691523, 1, NULL, '', 1, 2, NULL, '2025-08-26 15:13:45', 'PKL-AA22', '8'),
(79, 5, 'Dear ', 'Mathew', 'mathewadur@gmail.com', '9895551860', '', '', 'Pathaanamthitta ', 'Kerala ', 691523, 1, NULL, '', 1, 2, NULL, '2025-08-26 15:13:45', 'PKL-AA23', '8'),
(80, 5, 'Dear ', 'Sethupathy', '', '9995315596', '', '', 'Melarannor', 'kerala', 900001, 1, NULL, '', 1, 2, NULL, '2024-12-30 14:58:14', 'PKL-AA34', '8'),
(81, 5, 'Dear ', 'Tina mandonsa', 'Yamuna@gmail.com', '', '', '', 'Bangalore ', 'Karnataka', 560084, 1, NULL, '', 1, 2, NULL, '2025-08-26 15:13:45', 'PKN-AA24', '8'),
(82, 5, 'Dear ', 'SANTHOSH CK', 'mkdsanthoshck@gmail.com', '7907587685', 'Elegant ev charging station', NULL, 'Mannarkkad', 'India', 678583, 1, NULL, '33AAHCT1842P1Z0', 1, 2, NULL, '2026-03-12 01:34:02', 'TIN-AA38', '8'),
(83, 5, 'Dear ', 'Ettivilayil Kittan Prasad ', 'prasad.hamco@yahoo.com', '8078184444', 'Shamayam Supermarket,Pathalil Building', 'Makkpuzha,placherry', 'Ranni', 'Kerala', 689676, 1, NULL, 'GSTIN:32ACBPE6076B2Z8', 1, 2, NULL, '2022-08-24 15:09:20', 'TKL-AA02', '8'),
(84, 5, 'Dear ', 'Aneesh', 'Ultralink@gmail.com', '9443117632', 'Aneesh Buyju, Near Chenkavila overbridge', 'Cheruvarakonam, Thiruvananthapuram', 'Thiruvananthapuram', 'Kerala', 695502, 1, 'Ultralink EV Charging Station', 'GSTIN:33AAHCT1842P1Z0', 1, 2, NULL, '2025-08-26 15:13:45', 'TKL-AA03', '8'),
(85, 5, 'Dear ', 'Krishnamenon', 'Electrigo@gmail.com', '9645322221', 'B5 ROAD', 'Madurai', 'Alappuzha', 'Kerala', 688525, 1, 'Triveni EV Charging Station', 'GSTIN:33AAHCT1842P1Z0', 1, 2, NULL, '2023-12-08 07:28:17', 'TKL-AA04', '8'),
(86, 5, 'Dear ', 'Rathish', 'rathish@gmail.com', '9995360999', 'FXM4+W23', 'Thiruvananthapuram, Kerala', 'Thiruvananthapuram ', 'Kerala', 600002, 1, 'Rathish EV Chargers', '', 1, 2, NULL, '2025-08-26 15:13:45', 'TKL-AA15', '8'),
(87, 5, 'Dear ', 'Manumurali', 'zeed4ev@gmail.com', '7306297982', '', '', 'Alappuzha', 'Kerala', 600002, 1, NULL, 'GSTIN:32AADFZ9841R1ZB ', 1, 2, NULL, '2025-08-26 15:13:45', 'TKL-AA16', '8'),
(88, 5, 'Dear ', 'Apartment Association (Ganga)	', 'kpnair1952@gmail.com', '8921738890 ', 'CR Builders, Souparnika, CIT Rd, Melarannoor', ' Karamana, Thiruvananthapuram, Kerala', 'thiruvananthapuram', 'Kerala', 695002, 1, 'Ganga Apartment EV Chargers', 'GSTIN:33AAHCT1842P1Z0', 1, 2, NULL, '2025-08-26 15:13:45', 'TKL-AA25', '8'),
(89, 5, 'Dear ', 'Apartment Association (Yamuna)', 'yamunawellfare2021@gmail.com', '9447042242', 'CR Builders, Souparnika, CIT Rd, Melarannoor, Karamana', 'Thiruvananthapuram, Kerala', 'thiruvananthapuram', 'Kerala', 695002, 1, 'Yamuna Apartment EV Chargers', 'GSTIN:33AAHCT1842P1Z0', 1, 2, NULL, '2025-08-26 15:13:45', 'TKL-AA26', '8'),
(90, 5, 'Dear ', 'Cordials magnolia owners Association', 'insanesethu@gmail.com', '9567226422', 'P. O, TC 23/1302 1st Floor, ', 'CIT Road Near KSEB Office, Aarannoor, Surya Nagar, Karamana, ', 'Thiruvananthapuram', 'kerala', 695002, 1, 'Cordial Magnolia Apartment EV Chargers', '', 1, 2, NULL, '2024-12-30 14:58:14', 'TKL-AA32', '8'),
(91, 5, 'Dear ', 'AJAY KUMAR N', 'ajaynarikkuni@gmail.com', '9846909744', 'NELLIERY HOUSE,NARIKKUNI POST,KOZHIKODE-673585', NULL, 'NARIKKUNI', 'KERALA', 673585, 1, NULL, '33AAHCT1842P1Z0', 1, 2, NULL, '2026-02-24 11:59:59', 'TKL-AA36', '8'),
(92, 5, 'Dear ', 'Sreehari Sk', 'sreeharisk19@gmail.com', '7736650064', 'Mele Kodakkad, Mannarkkad', 'Kottoppadam-II, Kerala, pallakad, Kerala', 'Calicut', 'Kerala', 678583, 1, 'Elegent ev charging station', '33AAHCT1842P1Z0', 1, 2, NULL, '2026-02-28 15:02:01', 'TKL-AA37', '8'),
(93, 5, 'Dear ', 'Madhuban', 'madhuban@gmail.com', '9226508326', '', '', 'Pune', 'Maharashtra', 600002, 1, NULL, '', 1, 2, NULL, '2025-08-26 15:13:45', 'TMH-AA17', '8'),
(94, 5, 'Dear ', 'Kataria', 'rushabh@gmail.com', '9819554654', '', '', 'Coimbatore ', 'Tamilnadu ', 641025, 1, NULL, '', 1, 2, NULL, '2025-08-26 15:13:45', 'TMH-AA18', '8'),
(95, 5, 'Dear ', 'Nizam Shaikh', 'summertower@gmail.com', '9967410131', '', '', 'Coimbatore ', 'Tamilnadu', 641025, 1, NULL, 'GSTIN:33AHEPM0753H1ZM', 1, 2, NULL, '2025-08-26 15:13:45', 'TMH-AA19', '8'),
(96, 5, 'Dear ', 'Jayesh Zangda', 'cymoline@gmail.com', '9324692404', 'Shop No. 12, Om Sai Vyapari Mandal,', 'G.M.Road,', 'Chembur', 'Mumbai', 400089, 1, NULL, 'GSTIN: 27AAAPZ0264B1ZY', 1, 2, NULL, '2022-08-24 15:09:20', 'TMH-AA20', '8'),
(98, 5, 'Dear ', 'Raghu ', 'md@riogranderesidency.com', '967738889 ', 'B5 ROAD', 'Madurai', 'MADURAI', 'Tamilnadu ', 625016, 1, NULL, 'GSTIN:33AAHCT1842P1Z0', 1, 2, NULL, '2022-08-24 15:09:20', 'TTN-AA05', '8'),
(100, 5, 'Dear ', 'Pandiyan ', 'SNJfood @gmail.com', '8610344848', 'Door 25/3 ,South Street,', 'Panankulam Karanthaneri (Panchayat),', 'Nanguneri', 'Tamilnadu ', 627152, 1, 'SNJ Charging Station', 'GSTIN:33AAHCT1842P1Z0', 1, 2, NULL, '2025-01-01 07:28:17', 'TTN-AA07', '8'),
(101, 5, 'Dear ', 'Rajaji', 'avr@gmail.com', '8778184626', '892, N 2nd St, Wadair santha, Jeeva Nagar, Near 100mt rani hosipatal, Pudukkottai, Tamilnadu', '892, N 2nd St, Wadair santha, Jeeva Nagar, Near 100mt rani hosipatal, Pudukkottai, Tamilnadu', 'Pudukkottai', 'Tamilnadu ', 600002, 1, NULL, 'GSTIN:33AAHCT1842P1Z0', 1, 2, NULL, '2025-08-26 15:13:45', 'TTN-AA08', '8'),
(102, 5, 'Dear ', 'Kaviyarasu ', 'duriyam@gmail.com', '9655321578', 'Avadi', 'Chennai', 'Namakkal', 'Tamilnadu ', 600002, 1, NULL, 'GSTIN:33BAHPK3779E1ZO', 1, 2, NULL, '2022-04-16 15:13:45', 'TTN-AA09', '8'),
(103, 5, 'Dear ', 'Vasanth   ', 'srivari@gmail.com', '9629461203', '', '', 'Tirupattur ', 'Tamilnadu ', 635652, 1, NULL, '', 1, 2, NULL, '2025-08-26 15:13:45', 'TTN-AA11', '8'),
(104, 5, 'Dear ', 'G.Senthilvel', 'senthil@gmail.com', '8778585331', '', '', 'Kallakurichi ', 'Tamilnadu ', 600002, 1, NULL, '', 1, 2, NULL, '2025-08-26 15:13:45', 'TTN-AA12', '8'),
(105, 5, 'Dear ', 'Charzeup voltere', 'vasantham@gmail.com', '7550044001', '', '', 'Chinna salem', 'Tamilnadu ', 600002, 1, NULL, 'GSTIN:33AAHCT1842P1Z0', 1, 2, NULL, '2025-08-26 15:13:45', 'TTN-AA13', '8'),
(106, 5, 'Dear ', 'Danish', 'onyx@gmail.com', '9003383082', '', '', 'Mulagumoodu', 'Tamilnadu ', 600002, 1, NULL, '', 1, 2, NULL, '2025-08-26 15:13:45', 'TTN-AA14', '8'),
(108, 5, 'Dear ', 'Nandhini Murugan', 'nandhinishri31@gmail.com', '9442774866', '238/2, Vinayagar kovil street - 10 ', 'Kasipalayam, Erode', 'Erode', 'Tamilnadu', 638010, 1, 'Vishalini Enterprises', '', 1, 2, NULL, '2025-11-04 15:50:08', 'TTN-AA35', '8'),
(111, 5, 'Mr.', 'Rishab (rebolt technologies)', 'rebolt@gmail.com', '+91 88672 22331', '190/1, indira nagar, manikavsakar streetm, madurai', '', 'Bengaluru', 'Karnataka', 900001, 7, '', '', 1, 2, '', '2024-12-30 14:58:14', 'WKN-AA10', '5'),
(112, 5, 'Dear ', 'Wuerth EV Chargers', 'wuerth@gmail.com', '', '', '', 'Mumbai', 'Mumbai', 600002, 1, NULL, '', 1, 2, NULL, '2025-08-26 15:13:45', 'WMH-AA28', '3'),
(113, 5, 'Mr.', 'Rahul', 'global@gmail.com', '9960699963', 'Flat No 1/7, Kanchan Villa,', '', 'Amravati', 'Maharashtra', 444608, 5, 'TRYK Charge Services LLP', '27AAUFT5931H1ZL', 1, 2, '', '2024-12-30 14:58:14', 'WMH-AA31', '7'),
(114, 5, 'Mr.', 'Prakash dhage', 'ioemass@gmail.com', '82085 34087', 'SN 06, Hissa No 3A, Deshmukh Nagar, NDA Road Shivane, Pune 411023, India, Pune, Maharashtra', '', 'Pune', 'Maharashtra', 900001, 5, '', '', 1, 2, '', '2024-12-30 14:58:14', 'WMH-AA33', '7'),
(115, 5, 'Mr.', 'Balaji', 'charznet@gmail.com', '9840255758', 'B5 ROAD', 'Madurai', 'MADURAI', 'Tamilnadu', 625016, 1, '', 'GSTIN:36AAICV4869P1ZA', 1, 2, '', '2022-08-24 15:09:20', 'WTG-AA29', '8'),
(116, 5, 'Mr.', 'Madhavendra', 'fatafat@gmail.com', '70814 54444', 'Sarai Sahjadi, Banthara Bazar, Near Ramada Plaza, Kanpur Road', 'Madura', 'Lucknow', 'Uttar Pradesh', 226401, 1, 'Phataphat Climate Ventures Private Limited', '', 1, 7, '', '2023-12-08 07:28:17', 'WUP-AA27', '4'),
(140, 5, 'Mr.', 'Rajasekar', '', '9176444114', 'Karaikal', '', 'Karaikal', 'TamilNadu', 609602, 1, '', '', 1, 2, '', '2026-04-20 06:05:33', 'TTA-AA41', '8'),
(146, 5, 'Mr.', 'Aravind', 'enquiry@enerzise.com', '7769980429', '123/18 6th Cross CV raman nagar', '', 'Bangalore', 'Karnataka', 560093, 5, 'Enerzise Power Solutions', '29ASGPC6026Q1ZB', 1, 1, '', '2026-04-24 08:24:40', 'TKA-AA42', '7'),
(149, 5, 'Mr.', 'Bhaskar Kumar', 'titan.geneticscropsciences@gmail.com', '7903801770', 'Plot No.F/8 ,Atwal Nagar, 80ft Road', '', 'Kota', 'Rajasthan', 324001, 4, 'Titan Genetics & Crop Sciences', '', 1, 7, '', '2026-05-04 04:30:24', 'TRA-AA45', '8'),
(150, 5, 'Mr.', 'P.A Praveen', 'praveenpa@gmail.com', '9488420795', '82,East 3rd Street, TRV Nagar, Arupukottai', '', 'Virudhunagar', 'TamilNadu', 626101, 1, '', '', 1, 7, '', '2026-05-04 07:03:42', 'TTA-AA45', '8'),
(151, 5, 'Mr.', 'Vithal Rao Kanchi', 'vithalrao@gmail.com', '9848306474', 'Kodangal', '', 'Mahabub', 'Telagana', 509338, 1, 'Avira Services', '', 1, 1, '', '2026-05-06 06:16:38', 'TTE-AA45', '8'),
(152, 5, 'Mr.', 'Rakesh', 'bsbrakesh.22@gmail.com', '9143911234', 'H NO 25-15-11 vackala gadda van narasimharao pet, Opp Z P Office Road, Eluru', '', 'West Godavari', 'AndhraPradesh', 534006, 1, '', '', 1, 1, '', '2026-05-06 08:34:31', 'TAN-AA45', '8'),
(154, 5, 'Mr.', 'Fusiontek', 'fusiontek4559@gmail.com', '7406661555', 'Q11,3rd main kssidc industrial estate, veersandra, 2nd stage, electronic City phase 2', '', 'Bangalore', 'Karnataka', 560100, 1, '', '', 1, 3, '', '2026-05-07 04:42:54', 'TKA-AA45', '8'),
(155, 5, 'Mr.', 'Laxmanan', 'laxmanan@gmail.com', '7530014001', 'Rayarpalayam, Tiruchengode', '', 'Namakkal', 'TamilNadu', 641659, 1, '', '', 1, 6, '', '2026-05-08 07:02:43', 'TTA-AA45', '8'),
(157, 5, 'Mr.', 'Gobinath', 'care@zeoncharging.com', '9150716161', '167, Union Mill Rd,', 'Near 12R Kalayana Mandapam, Valipalaylam', 'Tiruppur', 'Tamil Nadu', 641601, 4, 'Zeon Electric Pvt Ltd', '', 1, 6, '', '2026-05-08 09:57:12', 'TTA-AA45', '8'),
(158, 4, 'Mr.', 'johnson jacob', 'info@assethomes.in', '9946069652', 'asset homes', '', 'ernakulam', 'kerala', 682304, 3, 'asset homes', '', 1, 2, 'ev charging station', '2026-05-08 10:07:45', 'TKE-AA45', '8'),
(161, 5, 'Mr.', 'Valluru Satwik Reddy', 'vallurusatwikreddy@gmail.com', '9966540954', '39/631-9, Aravind Nagar, Patel road-2', '', 'Kadappa', 'Andhra Pradesh', 516001, 1, '', '', 1, 6, '', '2026-05-09 04:38:42', 'TAN-AA45', '8'),
(162, 5, 'Mr.', 'Valluru Satwik Reddy', 'vallurusatwikreddy@gmail.com', '9885555572', '39/631-9, Aravind Nagar, Patel road-2', '', 'Kadappa', 'AndhraPradesh', 516001, 1, 'Madhavi convention', '', 1, 6, '', '2026-05-09 04:41:37', 'TAN-AA45', '8'),
(166, 5, 'Mr.', 'S. Venkateswara Pandiyan', 'pandianrohi9443@gmail.com', '8825402833', '33,Madha Nagar,2nd Street, Govt School opposite,', 'Illupakkudi', 'Karaikudi', 'TamilNadu', 630202, 1, '', '', 1, 6, '', '2026-05-12 04:56:47', 'TTA-AA45', '8'),
(167, 5, 'Mr.', 'Khush Chandawat', 'khush@harbocharge.com', '9449577498', 'No. 10/4, Chandawat, Devanathchar Road, Chamarajpet', '', 'Bangalore', 'Karnataka', 560018, 5, 'Harboline Ventures Private Limited', '29AAHCH4485J1Z0', 1, 7, '', '2026-05-13 04:47:50', 'TKA-AA46', '7'),
(168, 5, 'Mr.', 'Ramalingam H', 'rampurchase@teemageprecast.in', '8862884528', '6/35, College Road, 1st cross Street', '', 'Tiruppur', 'TamilNadu', 641602, 1, 'Teemage Precast In', '641', 1, 6, '', '2026-05-13 06:01:22', 'TTA-AA46', '8'),
(169, 5, 'Mr.', 'SushilKumar Tulsiram Sarda', 'info@prithvicharge.com', '8610307797', 'C-1, G1-2, Gangatri Apartment, Ring Road,', '', 'Indore', 'Madhya Pradesh', 452018, 4, 'Prithvi Charge Pvt Ltd', '', 1, 2, '', '2026-05-13 08:00:28', 'TMA-AA46', '8'),
(170, 5, 'Mr.', 'Selvaraj', 'selvaraj@gmail.com', '9444226395', 'No.7, Bharathi Nagar, near vision school, MC Road,', '', 'Thanjavur', 'TamilNadu', 613010, 1, '', '', 1, 6, '', '2026-05-14 06:53:03', 'TTA-AA46', '8'),
(171, 5, 'Mr.', 'KM Construction', 'Projects@kmconstructions.co.in', '9566622632', 'No.10, North gate ss colony,', '', 'Madurai', 'TamilNadu', 625019, 1, '', '', 1, 6, '', '2026-05-14 07:47:04', 'TTA-AA46', '8'),
(172, 5, 'Mr.', 'Bilal', 'er.bilal.007@gmail.com', '7838921271', 'C-152 Ekta vihar South', 'Rampur Dauhra', 'Moradabad', 'Uttar Pradesh', 244001, 1, '', '', 1, 6, 'Franchise Requirement Nearby Uttar Pradesh', '2026-05-15 08:33:00', 'TUT-AA46', '8'),
(177, 5, 'Mr.', 'Sree', 'a4international1712@gmail.com', '6381455579', 'No.4/2, 2nd Floor, Muthukrishna Flat, Kalaingnar street,Avadi,', '', 'Chennai', 'TamilNadu', 600054, 1, '', '', 1, 6, '', '2026-05-19 05:53:04', 'TTA-AA46', '8'),
(180, 5, 'Mr.', 'Hero Vivek', 'vivek6383008291@gmail.com', '09567226422', 'kl dfgdfg dfgdftdr fgdfgdfg', 'klgdfgdfgdfgn dfgdfgdfgdftg', 'Melarannor', 'Kerala', 900001, 1, 'Zeon Electric Pvt Ltd', '', 1, 2, '', '2026-05-19 12:30:47', 'TKE-AA47', '8'),
(181, 5, 'Mr.', 'Anand', 'jey@frezhminds.com', '9843267895', 'Plot No. 5/89-3, 3A, Ward \"C Block 19, Chettiyar Park Road, MM Street', '', 'Kodaikanal', 'TamilNadu', 624101, 1, 'VC Elite Residency', '33AAMFV6542J1ZS', 1, 6, '', '2026-05-20 04:31:47', 'TTA-AA47', '8'),
(182, 5, 'Mr.', 'Dhanasekar', 'sekarnithish20@gmail.com', '9500656896', '2/429,pallivasal street, Gandhi nagar, Batlagundu', '', 'Dindigul', 'TamilNadu', 624202, 1, '', '', 1, 1, '', '2026-05-20 07:49:08', 'TTA-AA47', '8'),
(183, 4, 'Mr.', 'kuruvila george', 'kuruvila999@gmail.com', '9995964424', 'chakuvalli bharanikavu', '', 'kollam', 'keraka', 690522, 1, '', '', 1, 2, '30 kw fast charger', '2026-05-21 05:35:18', 'TKE-AA47', '8'),
(184, 5, 'Mr.', 'king', 'support@tuckermotors.com', '6383008291', 'kl dfgdfg dfgdftdr fgdfgdfg', '', 'Melarannor', 'Kerala', 900001, 2, 'Zeon Electric Pvt Ltd', 'GST334455', 1, 2, 'test', '2026-05-21 09:15:58', 'TKL-AA47', '8'),
(185, 5, 'Mr.', 'N. Venkatachalam', 'sivabalaajisteel@gmail.com', '6384841004', '18B Natham Road', '', 'Dindigul', 'TamilNadu', 624003, 1, 'Shree SivaBalaaji Steels Private Limited', '', 1, 6, '', '2026-05-22 06:52:07', 'TTA-AA47', '8'),
(186, 5, 'Mr.', 'Rajalakshmi Ponnuswami', 'evzonemadurai@gmail.com', '8400016276', 'Max Woods, Flat No.27, Kilakuyilkudi, Thattanur', '', 'Madurai', 'TamilNadu', 625019, 1, 'EV Zone Madurai', '', 1, 1, '', '2026-06-05 04:39:23', 'TTA-AA45', '8'),
(187, 5, 'Mr.', 'Hameed', 'malleshpathipati@gmail.com', '87542 87699', '152, balaji nagar,', '', 'Chitoor', 'AndhraPradesh', 517002, 1, '', '', 1, 6, '', '2026-06-06 06:34:53', 'TAN-AA45', '8'),
(188, 5, 'Mr.', 'Karthi', 'Erostr2026@gmail.com', '9842184455', 'Bhavani, Lakshmi Nagar', '', 'Erode', 'TamilNadu', 638052, 1, 'ERO STR CAFE', '', 1, 2, '', '2026-06-08 09:50:41', 'TTA-AA45', '8'),
(189, 5, 'Mr.', 'Vinod Kumar', 'vinod@gmail.com', '6379335587', 'Erode', '', 'Erode', 'TamilNadu', 638052, 4, '', '', 1, 6, '', '2026-06-08 10:22:01', 'TTA-AA45', '8'),
(190, 5, 'Mr.', 'Balasubramani', 'balasubramani@gmail.com', '6379923989', 'Palladam', '', 'Coimbatore', 'TamilNadu', 641664, 1, '', '', 1, 6, '', '2026-06-08 10:43:03', 'TTA-AA45', '8'),
(191, 5, 'Mr.', 'Karuna Sagar T', 'karunasagar125@gmail.com', '9003498293', 'No.3, Bharath Villa, Jawahar 1st street, SS colony', '', 'Madurai', 'TamilNadu', 625018, 1, '', '', 1, 6, '', '2026-06-09 08:01:35', 'TTA-AA45', '8');

-- --------------------------------------------------------

--
-- Table structure for table `lead_messages`
--

CREATE TABLE `lead_messages` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `lead_messages`
--

INSERT INTO `lead_messages` (`id`, `lead_id`, `message`, `created_at`) VALUES
(1, 160, 'hi', '2026-05-09 11:49:29'),
(2, 160, 'ht', '2026-05-11 11:20:41');

-- --------------------------------------------------------

--
-- Table structure for table `lead_sources`
--

CREATE TABLE `lead_sources` (
  `id` int(11) NOT NULL,
  `source_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `lead_sources`
--

INSERT INTO `lead_sources` (`id`, `source_name`) VALUES
(1, 'Website'),
(2, 'Referral'),
(3, 'Social Media'),
(4, 'Email Campaign'),
(5, 'Event'),
(6, 'Over the call '),
(7, 'Indiamart'),
(8, 'CGR Mart'),
(9, 'Inventory');

-- --------------------------------------------------------

--
-- Table structure for table `lead_statuses`
--

CREATE TABLE `lead_statuses` (
  `id` int(11) NOT NULL,
  `status_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `lead_statuses`
--

INSERT INTO `lead_statuses` (`id`, `status_name`) VALUES
(1, 'Total Enquiries'),
(2, 'Quotations Issued'),
(3, 'Total Quoted Amount'),
(4, 'Amount Collected'),
(5, 'Outstanding Amount'),
(6, 'Converted Quotations');

-- --------------------------------------------------------

--
-- Table structure for table `login_details`
--

CREATE TABLE `login_details` (
  `id` int(11) DEFAULT NULL,
  `username` varchar(45) NOT NULL,
  `password` varchar(45) DEFAULT NULL,
  `mobile_no` varchar(15) DEFAULT NULL,
  `otp` varchar(10) DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `login_details`
--

INSERT INTO `login_details` (`id`, `username`, `password`, `mobile_no`, `otp`, `last_login_ip`, `otp_expiry`) VALUES
(1, 'admin', 'admin123', '8925964348', NULL, '49.47.241.219', NULL),
(2, 'Master_tucadmin', '@Tucker100', '8925964348', NULL, '49.47.240.15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `payment_mode` varchar(50) DEFAULT NULL,
  `total_amount` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `proof_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `related_id`, `payment_mode`, `total_amount`, `amount`, `payment_reference`, `proof_file`, `created_at`) VALUES
(8, 27, 'Bank Transfer', '1119000', '1119000.00', 'S95109764', 'pay_69b8daa13e25a9.60353990.pdf', '2026-03-17 04:37:53'),
(9, 13, 'Bank Transfer', '13545', '13545.00', '2291545588', 'pay_69b8dd75c92a51.24996605.pdf', '2026-03-17 04:49:57'),
(19, 28, 'Cash', '1116000', '200000.00', 'Cash by hand(MD)', NULL, '2026-03-18 06:18:02'),
(31, 39, 'Bank Transfer', '20475', '20475.00', 'S96326471', 'pay_69c9fc323bf323.55081806.png', '2026-03-30 04:29:38'),
(32, 28, 'Cash', '1116000', '200000.00', 'Cash by hand(MD)', NULL, '2026-03-30 05:01:32'),
(68, 81, 'Cash', '975910.41', '96855.00', 'cash', NULL, '2026-04-06 10:29:35'),
(69, 80, 'Cash', '5904.8', '5904.80', 'handed to Amsath', NULL, '2026-04-18 10:24:50'),
(70, 88, 'Bank Transfer', '916100', '916100.00', 'vineeth', NULL, '2026-04-18 11:46:38'),
(71, 87, 'Bank Transfer', '188000', '170000.00', 'vineeth', NULL, '2026-04-18 11:48:10'),
(72, 95, 'Cash', '2134524.51', '10000.00', 'cash', NULL, '2026-04-21 08:32:42'),
(73, 96, 'Bank Transfer', '28428', '28428.00', 'UCBAH26111781287', 'pay_69e868c64ce641.50310372.pdf', '2026-04-22 06:20:54'),
(82, 102, 'Bank Transfer', '15750', '10000.00', 'IDFB611479794822', NULL, '2026-05-02 07:15:53'),
(83, 84, 'Bank Transfer', '24675', '24675.00', '00000', NULL, '2026-05-02 07:18:36'),
(84, 87, 'Cash', '188000', '18000.00', '.', NULL, '2026-05-05 05:16:49'),
(85, 117, 'Bank Transfer', '4200', '4200.00', '612612602088', 'pay_69faf248b7b096.33820334.jpeg', '2026-05-06 07:48:24'),
(86, 102, 'Bank Transfer', '15750', '5750.00', '612612602088', 'pay_69faf2807d01e5.62381053.jpeg', '2026-05-06 07:49:20'),
(103, 138, 'Cash', '6972000', '1.00', '0', NULL, '2026-05-13 08:27:12'),
(144, 159, 'Cash', '27456.24', '10000.00', '0', NULL, '2026-05-19 11:19:25'),
(156, 132, 'Bank Transfer', '40530.00', '10000.00', 'CBINN62026051404003011', 'pay_6a0d3fe8951cc1.28503187.jpeg', '2026-05-20 05:00:24'),
(157, 121, 'Bank Transfer', '97650', '48825.00', '2387555443', 'pay_6a0d40d4a87d19.53316883.pdf', '2026-05-20 05:04:20'),
(158, 162, 'Cash', '9152.08', '1500.00', '0', NULL, '2026-05-20 05:18:15'),
(179, 175, 'Bank Transfer', '1226100', '500000.00', 'S75521871', 'pay_6a1a7083e035f4.11792790.jpeg', '2026-05-30 05:07:15'),
(180, 179, 'Bank Transfer', '819000', '800000.00', 'HDFC7F6D298C9F14', 'pay_6a1fe1c3a404f5.92175717.jpeg', '2026-06-03 08:11:47'),
(181, 180, 'UPI', '10500', '10500.00', 'gpay', 'pay_6a27abb12ad2c1.70444382.jpeg', '2026-06-09 05:59:13'),
(182, 186, 'Bank Transfer', '4725', '4725.00', '2411947526', 'pay_6a27b42a3d8759.06061266.jpeg', '2026-06-09 06:35:22');

-- --------------------------------------------------------

--
-- Table structure for table `productss`
--

CREATE TABLE `productss` (
  `id` int(11) NOT NULL,
  `quotation_id` int(11) DEFAULT NULL,
  `product_id` varchar(255) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `gst_percent` decimal(5,2) DEFAULT 0.00,
  `total_price` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `productss`
--

INSERT INTO `productss` (`id`, `quotation_id`, `product_id`, `product_name`, `unit_price`, `quantity`, `discount_percent`, `gst_percent`, `total_price`) VALUES
(1, 1, 'TCP-120-D-0-004', 'Dc fast charger Dual gun 120kW – Ethernet', '950000.00', 1, '0.00', '5.00', '997500.00'),
(2, 2, 'TCP-120-D-0-002', 'Dc fast charger Dual gun 120kW – Wi-Fi', '950000.00', 1, '5.26', '5.00', '945031.50'),
(3, 3, 'TCP-060-D-0-002', 'Dc fast charger Dual gun 60kW – Wi-Fi', '680000.00', 1, '4.40', '5.00', '682584.00'),
(4, 4, 'TCP-003-S-0-002', 'Type 2 personal charger 3.3 – Wi-Fi', '19999.00', 1, '1.00', '4.00', '20590.97'),
(5, 4, 'TCP-003-S-0-003', 'Type 2 personal charger 3.3 – RFID', '18599.00', 1, '24.73', '5.00', '14699.44'),
(6, 4, 'TCP-003-S-0-003', 'Type 2 personal charger 3.3 – RFID', '18599.00', 1, '24.73', '5.00', '14699.44'),
(7, 5, 'TCP-003-S-1-123', 'Sumo 3.3 – Wi-Fi + GSM + RFID', '4300.00', 5, '0.24', '5.00', '22520.82'),
(8, 6, 'TCP-003-S-1-001', 'Sumo 3.3 – GSM', '4500.00', 2, '0.00', '5.00', '9450.00'),
(9, 7, 'TCP-003-S-1-013', 'Sumo 3.3 – GSM + RFID', '4500.00', 2, '0.00', '5.00', '9450.00'),
(10, 7, 'M2M SIM 4G', 'M2M SIM 4G', '35.00', 2, '0.00', '18.00', '82.60'),
(11, 7, 'M2M SIM(Data Charge)', 'M2M SIM(Data Charge)', '75.00', 2, '0.00', '18.00', '177.00'),
(12, 8, 'TCP-003-S-1-001', 'Sumo 3.3 – GSM', '7700.00', 1, '28.57', '5.00', '5775.12'),
(13, 9, 'TCP-003-S-1-001', 'Sumo 3.3 – GSM', '7700.00', 1, '28.57', '5.00', '5775.12'),
(15, 10, 'TCP-003-S-1-001', 'Sumo 3.3 – GSM', '7700.00', 1, '28.57', '5.00', '5775.12'),
(16, 10, 'TCP-003-S-2-123', 'Nexo 3.3 – Wi-Fi + GSM + RFID', '7700.00', 1, '51.96', '5.00', '3884.03'),
(17, 11, 'TCP-003-S-1-001', 'Sumo 3.3 – GSM', '7700.00', 1, '28.57', '5.00', '5775.12'),
(18, 11, 'TCP-003-S-2-123', 'Nexo 3.3 – Wi-Fi + GSM + RFID', '7700.00', 1, '51.96', '5.00', '3884.03'),
(19, 12, 'TCP-003-S-0-002', 'Type 2 personal charger 3.3 – Wi-Fi', '25000.00', 1, '28.00', '5.00', '18900.00'),
(20, 13, 'TCP-003-S-1-123', 'Sumo 3.3 – Wi-Fi + GSM + RFID', '4300.00', 3, '0.00', '5.00', '13545.00'),
(21, 14, 'TCP-003-S-1-023', 'Sumo 3.3 – Wi-Fi + RFID', '5000.00', 1, '0.00', '5.00', '5250.00'),
(22, 14, 'TCP-060-D-0-002', 'Dc fast charger Dual gun 60kW – Wi-Fi', '600000.00', 1, '0.00', '5.00', '630000.00'),
(23, 14, 'TCP-120-D-0-002', 'Dc fast charger Dual gun 120kW – Wi-Fi', '780000.00', 1, '0.00', '5.00', '819000.00'),
(24, 15, 'TCP-003-S-0-002', 'Type 2 personal charger 3.3 – Wi-Fi', '25000.00', 1, '28.00', '5.00', '18900.00'),
(25, 15, 'TCP-003-S-1-001', 'Sumo 3.3 – GSM', '7700.00', 1, '0.00', '5.00', '8085.00'),
(26, 15, 'TCP-003-S-0-002', 'Type 2 personal charger 3.3 – Wi-Fi', '25000.00', 1, '28.00', '5.00', '18900.00'),
(27, 15, 'TCP-008-S-0-003', 'Type 2 AC charger 7.4 – RFID', '24000.00', 1, '29.17', '5.00', '17849.16'),
(28, 15, 'TCP-080-D-0-002', 'Dc fast charger Dual gun 80kW – Wi-Fi', '820000.00', 1, '0.00', '4.00', '852800.00'),
(29, 15, 'TCP-022-S-0-002', 'Type 2 AC charger 22 – Wi-Fi', '36000.00', 1, '23.61', '5.00', '28875.42'),
(30, 15, 'TCP-080-D-0-002', 'Dc fast charger Dual gun 80kW – Wi-Fi', '820000.00', 1, '0.00', '5.00', '861000.00'),
(31, 16, 'TCP-003-S-2-002', 'Nexo 3.3 – Wi-Fi', '7500.00', 1, '0.00', '5.00', '7875.00'),
(32, 17, 'TCP-003-S-1-001', 'Sumo 3.3 – GSM', '5199.00', 2, '0.00', '18.00', '10398.00'),
(33, 18, 'TCP-003-S-1-001', 'Sumo 3.3 – GSM', '5199.00', 2, '0.00', '18.00', '10398.00'),
(34, 19, 'TCP-120-D-0-002', 'Dc fast charger Dual gun 120kW – Wi-Fi', '850000.00', 1, '2.30', '5.00', '871972.50'),
(35, 20, 'TCP-003-S-0-002', 'Type 2 personal charger 3.3 – Wi-Fi', '17999.00', 3, '0.00', '18.00', '53997.00'),
(36, 20, 'TCP-003-S-1-003', 'Sumo 3.3 – RFID', '3799.00', 1, '0.00', '18.00', '3799.00'),
(37, 21, 'TCP-003-S-1-001', 'Sumo 3.3 – GSM', '5199.00', 1, '0.00', '18.00', '5199.00'),
(38, 22, 'TCP-003-S-1-123', 'Sumo 3.3 – Wi-Fi + GSM + RFID', '5499.00', 1, '0.00', '18.00', '5499.00'),
(39, 23, 'TCP-003-S-1-003', 'Sumo 3.3 – RFID', '3799.00', 2, '0.00', '18.00', '7598.00'),
(40, 24, 'TCP-003-S-1-001', 'Sumo 3.3 – GSM', '5199.00', 2, '0.00', '18.00', '10398.00'),
(41, 25, 'TCP-003-S-1-003', 'Sumo 3.3 – RFID', '3799.00', 2, '0.00', '18.00', '7598.00'),
(42, 26, 'TCP-003-S-1-002', 'Sumo 3.3 – Wi-Fi', '4199.00', 1, '0.00', '18.00', '4199.00'),
(43, 26, 'TCP-003-S-2-001', 'Nexo 3.3 – GSM', '5499.00', 1, '0.00', '18.00', '5499.00'),
(44, 26, 'TCP-003-S-0-002', 'Type 2 personal charger 3.3 – Wi-Fi', '17999.00', 1, '0.00', '18.00', '17999.00'),
(45, 27, 'TCP-060-D-0-002', 'Dc fast charger Dual gun 60kW – Wi-Fi', '650000.00', 1, '0.00', '5.00', '682500.00'),
(46, 27, 'TCP-60-Franchise', '60kW DC Fast Charger (dual gun) |  Canopy | Safety Equipment (Camera &amp; Fire Extinguisher) | DB Panel | Installation &amp; Commissioning', '175000.00', 1, '0.00', '18.00', '206500.00'),
(47, 27, 'TCP-60-Franchise', '60kW DC Fast Charger (dual gun) |  Canopy | Safety Equipment (Camera &amp; Fire Extinguisher) | DB Panel | Installation &amp; Commissioning', '230000.00', 1, '0.00', '0.00', '230000.00'),
(48, 28, 'TCP-060-D-0-002', 'Dc fast charger Dual gun 60kW – Wi-Fi', '600000.00', 1, '0.00', '5.00', '630000.00'),
(49, 28, 'TCP-60-Franchise', '60kW DC Fast Charger (dual gun) |  Canopy | Safety Equipment (Camera &amp; Fire Extinguisher) | DB Panel | Installation &amp; Commissioning', '200000.00', 1, '0.00', '18.00', '236000.00'),
(50, 28, 'TCP-60-Franchise', '60kW DC Fast Charger (dual gun) |  Canopy | Safety Equipment (Camera &amp; Fire Extinguisher) | DB Panel | Installation &amp; Commissioning', '250000.00', 1, '0.00', '0.00', '250000.00'),
(70, 35, 'TCP-007-S-0-002', 'Type 2 AC Charger 7.4 – Wi-Fi', '28000.00', 1, '0.00', '5.00', '29400.00'),
(74, 39, 'TCP-003-S-1-123', 'Sumo 3.3 – Wi-Fi + GSM + RFID', '6500.00', 3, '0.00', '5.00', '20475.00'),
(78, 42, 'TCP-030-S-0-002', 'DC Fast Charger single gun 30kW – Wi-Fi', '380000.00', 1, '0.00', '5.00', '399000.00'),
(79, 43, 'TCP-060-D-0-002', 'DC Fast Charger Dual gun 60kW – Wi-Fi', '650000.00', 1, '0.00', '5.00', '682500.00'),
(80, 44, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '950000.00', 1, '0.00', '5.00', '997500.00'),
(82, 46, 'TCP-060-D-0-002', 'DC Fast Charger Dual gun 60kW – Wi-Fi', '650000.00', 1, '0.00', '5.00', '682500.00'),
(85, 0, 'TCP-003-S-2-001', 'Nexo 3.3 – GSM', '7487.00', 1, '26.55', '18.00', '6489.06'),
(110, 72, 'TCP-030-S-0-702', 'DC wall box 30kW – Wi-Fi', '400000.00', 1, '0.00', '5.00', '420000.00'),
(111, 73, 'TCP-030-S-0-004', 'DC Fast Charger single gun 30kW – Ethernet', '456000.00', 1, '0.00', '5.00', '478800.00'),
(117, 77, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '850000.00', 1, '0.00', '5.00', '892500.00'),
(118, 78, 'TCP-060-D-0-002', 'DC Fast Charger Dual gun 60kW – Wi-Fi', '650000.00', 2, '7.69', '5.00', '1260031.50'),
(119, 79, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '950000.00', 2, '7.36', '5.00', '1848168.00'),
(120, 80, 'TCP-003-S-2-023', 'Nexo 3.3 – Wi-Fi + RFID', '5500.00', 1, '0.00', '5.00', '5775.00'),
(121, 80, 'M2M SIM(Data Charge)', 'M2M SIM(Data Charge)', '75.00', 1, '0.00', '18.00', '88.50'),
(122, 80, 'M2M SIM 4G', 'M2M SIM 4G', '35.00', 1, '0.00', '18.00', '41.30'),
(123, 81, 'TCP-003-S-2-001', 'Nexo 3.3 – GSM', '7487.00', 5, '0.00', '5.00', '39306.75'),
(124, 81, 'TCP-011-S-0-002', 'Type 2 AC Charger 11 – Wi-Fi', '30918.00', 6, '28.84', '5.00', '138607.87'),
(125, 81, 'TCP-030-S-0-004', 'DC Fast Charger single gun 30kW – Ethernet', '519762.00', 2, '26.89', '5.00', '797995.80'),
(126, 82, 'TCP-060-D-0-002', 'DC Fast Charger Dual gun 60kW – Wi-Fi', '650000.00', 1, '0.00', '5.00', '682500.00'),
(127, 83, 'TCP-003-S-1-002', 'Sumo 3.3 – Wi-Fi', '5500.00', 1, '0.00', '5.00', '5775.00'),
(128, 83, 'TCP-007-S-0-002', 'Type 2 AC Charger 7.4 – Wi-Fi', '18000.00', 1, '0.00', '5.00', '18900.00'),
(130, 84, 'TCP-007-S-0-002', 'Type 2 AC Charger 7.4 – Wi-Fi', '18000.00', 1, '0.00', '5.00', '18900.00'),
(131, 85, 'TCP-030-S-0-002', 'DC Fast Charger single gun 30kW – Wi-Fi', '380000.00', 1, '0.00', '5.00', '399000.00'),
(132, 86, 'TCP-060-D-0-002', 'DC Fast Charger Dual gun 60kW – Wi-Fi', '600000.00', 1, '0.00', '5.00', '630000.00'),
(133, 86, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '780000.00', 1, '0.00', '5.00', '819000.00'),
(134, 87, 'TCP-003-S-1-002', 'Sumo 3.3 – Wi-Fi', '4500.00', 24, '0.00', '0.00', '108000.00'),
(135, 87, 'TCP-022-S-0-002', 'Type 2 AC Charger 22 – Wi-Fi', '40000.00', 2, '0.00', '0.00', '80000.00'),
(136, 88, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '850000.00', 1, '0.00', '5.00', '892500.00'),
(137, 88, 'TransaportchargersDC', 'Transaport chargers DC', '20000.00', 1, '0.00', '18.00', '23600.00'),
(138, 89, 'TCP-003-S-1-023', 'Sumo 3.3 – Wi-Fi + RFID', '4500.00', 1, '0.00', '5.00', '4725.00'),
(139, 90, 'TCP-007-S-0-001', 'Type 2 AC Charger 7.4 – GSM', '20000.00', 1, '0.00', '5.00', '21000.00'),
(140, 90, 'M2M SIM 4G', 'M2M SIM 4G', '35.00', 1, '0.00', '18.00', '41.30'),
(141, 90, 'M2M SIM(Data Charge)', 'M2M SIM(Data Charge)', '75.00', 1, '0.00', '18.00', '88.50'),
(142, 91, 'dualgunType6DCfastcharger', '6kw(3+3) dual gun Type6 DC fast charger', '130000.00', 1, '0.00', '5.00', '136500.00'),
(143, 92, 'dualgunType6DCfastcharger', '6kw(3+3) dual gun Type6 DC fast charger', '130000.00', 1, '0.00', '5.00', '136500.00'),
(144, 93, 'dualgunType6DCfastcharger', '6kw(3+3) dual gun Type6 DC fast charger', '130000.00', 1, '0.00', '5.00', '136500.00'),
(145, 94, 'dualgunType6DCfastcharger', '6kw(3+3) dual gun Type6 DC fast charger', '150000.00', 1, '0.00', '5.00', '157500.00'),
(146, 95, 'TCP-011-S-0-002', 'Type 2 AC Charger 11 – Wi-Fi', '30918.00', 10, '28.84', '5.00', '231013.11'),
(147, 95, 'TCP-022-S-0-004', 'Type 2 AC Charger 22 – Ethernet', '38361.00', 2, '0.00', '5.00', '80558.10'),
(148, 95, 'TCP-060-D-0-004', 'DC Fast Charger Dual gun 60kW – Ethernet', '868073.00', 2, '0.00', '5.00', '1822953.30'),
(149, 96, 'TCP-003-S-1-123', 'Sumo 3.3 – Wi-Fi + GSM + RFID', '4400.00', 6, '0.00', '5.00', '27720.00'),
(150, 96, 'Charge Card (RFID)', 'Charge Card (RFID)', '100.00', 6, '0.00', '18.00', '708.00'),
(151, 97, 'TCP-003-S-0-002', 'Type 2 personal charger 3.3 – Wi-Fi', '20000.00', 1, '1.00', '5.00', '20790.00'),
(162, 102, 'TCP-007-S-0-002', 'Type 2 AC Charger 7.4 – Wi-Fi', '15000.00', 1, '0.00', '5.00', '15750.00'),
(163, 103, 'TCP-003-S-1-123', 'Sumo 3.3 – Wi-Fi + GSM + RFID', '7756.00', 5, '0.00', '5.00', '40719.00'),
(164, 103, 'TCP-007-S-0-001', 'Type 2 AC Charger 7.4 – GSM', '39138.00', 1, '23.35', '5.00', '31499.24'),
(165, 103, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '1338937.00', 1, '0.00', '5.00', '1405883.85'),
(166, 104, 'TCP-003-S-1-123', 'Sumo 3.3 – Wi-Fi + GSM + RFID', '7756.00', 5, '0.00', '5.00', '40719.00'),
(167, 104, 'TCP-007-S-0-001', 'Type 2 AC Charger 7.4 – GSM', '39138.00', 1, '23.35', '5.00', '31499.24'),
(168, 104, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '1338937.00', 1, '0.00', '5.00', '1405883.85'),
(169, 105, 'TCP-003-S-1-002', 'Sumo 3.3 – Wi-Fi', '7350.00', 1, '0.00', '5.00', '7717.50'),
(171, 107, 'TCP-030-S-0-002', 'DC Fast Charger single gun 30kW – Wi-Fi', '380000.00', 1, '0.00', '5.00', '399000.00'),
(172, 107, 'TCP-060-D-0-002', 'DC Fast Charger Dual gun 60kW – Wi-Fi', '600000.00', 1, '0.00', '5.00', '630000.00'),
(173, 107, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '780000.00', 1, '0.00', '5.00', '819000.00'),
(174, 107, 'TCP-240-D-0-002', 'DC Fast Charger Dual gun 240kW – Wi-Fi', '1180000.00', 1, '0.00', '5.00', '1239000.00'),
(175, 107, 'TCP-007-S-0-002', 'Type 2 AC Charger 7.4 – Wi-Fi', '16000.00', 1, '0.00', '5.00', '16800.00'),
(176, 107, 'TCP-022-S-0-002', 'Type 2 AC Charger 22 – Wi-Fi', '30000.00', 1, '0.00', '5.00', '31500.00'),
(177, 108, 'TCP-060-D-0-002', 'DC Fast Charger Dual gun 60kW – Wi-Fi', '650000.00', 1, '0.00', '5.00', '682500.00'),
(178, 108, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '100000.00', 1, '0.00', '18.00', '118000.00'),
(179, 108, 'TCP-120-Canopy', 'Canopy', '120000.00', 1, '0.00', '18.00', '141600.00'),
(180, 108, 'TCP-80-Installation', 'Installation &amp; Commissioning', '50000.00', 1, '0.00', '18.00', '59000.00'),
(181, 108, 'TNEB Deposite Chargers', 'TNEB Deposite Chargers', '225000.00', 1, '0.00', '0.00', '225000.00'),
(182, 109, 'TCP-003-S-1-002', 'Sumo 3.3 – Wi-Fi', '4200.00', 1, '0.00', '5.00', '4410.00'),
(183, 110, 'TCP-060-D-0-002', 'DC Fast Charger Dual gun 60kW – Wi-Fi', '600000.00', 1, '0.00', '5.00', '630000.00'),
(184, 111, 'TCP-003-S-1-002', 'Sumo 3.3 – Wi-Fi', '4200.00', 1, '0.00', '5.00', '4410.00'),
(185, 112, 'TCP-003-S-1-002', 'Sumo 3.3 – Wi-Fi', '4200.00', 1, '0.00', '5.00', '4410.00'),
(186, 113, 'TCP-060-D-0-002', 'DC Fast Charger Dual gun 60kW – Wi-Fi', '600000.00', 1, '0.00', '5.00', '630000.00'),
(187, 114, 'TCP-060-D-0-002', 'DC Fast Charger Dual gun 60kW – Wi-Fi', '600000.00', 1, '0.00', '5.00', '630000.00'),
(188, 115, 'TCP-003-S-1-002', 'Sumo 3.3 – Wi-Fi', '4200.00', 1, '0.00', '5.00', '4410.00'),
(189, 116, 'TCP-003-S-1-002', 'Sumo 3.3 – Wi-Fi', '4200.00', 1, '0.00', '5.00', '4410.00'),
(190, 117, 'TCP-003-S-1-002', 'Sumo 3.3 – Wi-Fi', '4000.00', 1, '0.00', '5.00', '4200.00'),
(193, 119, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '950000.00', 3, '10.50', '5.00', '2678287.50'),
(194, 120, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '850000.00', 1, '0.00', '5.00', '892500.00'),
(195, 121, 'TCP-003-S-1-123', 'Sumo 3.3 – Wi-Fi + GSM + RFID', '4300.00', 10, '0.00', '5.00', '45150.00'),
(196, 121, 'TCP-011-S-0-002', 'Type 2 AC Charger 11 – Wi-Fi', '25000.00', 2, '0.00', '5.00', '52500.00'),
(197, 122, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '950000.00', 1, '0.00', '5.00', '997500.00'),
(198, 122, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '150000.00', 1, '0.00', '18.00', '177000.00'),
(199, 122, 'TCP-120-Canopy', 'Canopy', '120000.00', 1, '0.00', '18.00', '141600.00'),
(200, 122, 'TCP-80-Installation', 'Installation &amp; Commissioning', '50000.00', 1, '0.00', '18.00', '59000.00'),
(201, 123, 'TCP-007-S-0-002', 'Type 2 AC Charger 7.4 – Wi-Fi', '20000.00', 10, '0.00', '5.00', '210000.00'),
(202, 123, 'TCP-003-S-1-123', 'Sumo 3.3 – Wi-Fi + GSM + RFID', '5000.00', 10, '0.00', '5.00', '52500.00'),
(203, 124, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '950000.00', 2, '0.00', '5.00', '1995000.00'),
(204, 124, 'TCP-120-Canopy', 'Canopy', '120000.00', 2, '0.00', '18.00', '283200.00'),
(205, 124, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '150000.00', 2, '0.00', '18.00', '354000.00'),
(206, 124, 'TCP-80-Installation', 'Installation &amp; Commissioning', '50000.00', 2, '0.00', '18.00', '118000.00'),
(207, 125, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '950000.00', 1, '0.00', '5.00', '997500.00'),
(208, 125, 'TCP-120-Canopy', 'Canopy', '120000.00', 1, '0.00', '18.00', '141600.00'),
(209, 125, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '150000.00', 1, '0.00', '18.00', '177000.00'),
(210, 125, 'TCP-80-Installation', 'Installation &amp; Commissioning', '50000.00', 1, '0.00', '18.00', '59000.00'),
(211, 126, 'TCP-003-S-1-123', 'Sumo 3.3 – Wi-Fi + GSM + RFID', '7756.00', 5, '0.00', '18.00', '45760.40'),
(212, 127, 'TCP-003-S-1-123', 'Sumo 3.3 – Wi-Fi + GSM + RFID', '7756.00', 6, '0.00', '18.00', '54912.00'),
(213, 127, 'TCP-003-S-1-003', 'Sumo 3.3 – RFID', '5399.00', 1, '35.17', '18.00', '4130.20'),
(214, 128, 'TCP-003-S-1-123', 'Sumo 3.3 – Wi-Fi + GSM + RFID', '7756.00', 6, '0.00', '18.00', '54912.00'),
(215, 128, 'TCP-003-S-1-003', 'Sumo 3.3 – RFID', '5399.00', 1, '35.17', '18.00', '4130.20'),
(220, 130, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '950000.00', 1, '0.00', '5.00', '997500.00'),
(221, 131, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '900000.00', 1, '0.00', '5.00', '945000.00'),
(222, 131, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '130000.00', 1, '0.00', '18.00', '153400.00'),
(223, 131, 'TCP-120-Canopy', 'Canopy', '100000.00', 1, '0.00', '18.00', '118000.00'),
(224, 131, 'TCP-80-Installation', 'Installation &amp; Commissioning', '50000.00', 1, '0.00', '18.00', '59000.00'),
(225, 131, 'TNEB Deposite Chargers', 'TNEB Deposite Chargers', '368000.00', 1, '0.00', '18.00', '434240.00'),
(226, 132, 'TCP-003-S-1-002', 'Sumo 3.3 – Wi-Fi', '3800.00', 2, '0.00', '5.00', '7980.00'),
(227, 132, 'TCP-007-S-0-002', 'Type 2 AC Charger 7.4 – Wi-Fi', '15500.00', 2, '0.00', '5.00', '32550.00'),
(228, 133, '3KW Type 6 DC Fast Charger', '3KW Type 6 DC Fast Charger', '47000.00', 3, '0.00', '5.00', '148050.00'),
(229, 134, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '950000.00', 1, '0.00', '5.00', '997500.00'),
(230, 134, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '120000.00', 1, '0.00', '18.00', '141600.00'),
(231, 134, 'TCP-120-Canopy', 'Canopy', '10000.00', 1, '0.00', '18.00', '11800.00'),
(232, 134, 'TCP-80-Installation', 'Installation &amp; Commissioning', '50000.00', 1, '0.00', '18.00', '59000.00'),
(233, 134, 'TNEB Deposite Chargers', 'TNEB Deposite Chargers', '368000.00', 1, '0.00', '0.00', '368000.00'),
(234, 135, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '950000.00', 1, '0.00', '5.00', '997500.00'),
(235, 135, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '120000.00', 1, '0.00', '18.00', '141600.00'),
(236, 135, 'TCP-120-Canopy', 'Canopy', '100000.00', 1, '0.00', '18.00', '118000.00'),
(237, 135, 'TCP-80-Installation', 'Installation &amp; Commissioning', '50000.00', 1, '0.00', '18.00', '59000.00'),
(238, 135, 'TNEB Deposite Chargers', 'TNEB Deposite Chargers', '368000.00', 1, '0.00', '0.00', '368000.00'),
(241, 136, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '950000.00', 1, '0.00', '5.00', '997500.00'),
(242, 136, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '120000.00', 1, '0.00', '18.00', '141600.00'),
(243, 136, 'TCP-120-Canopy', 'Canopy', '100000.00', 1, '0.00', '18.00', '118000.00'),
(244, 136, 'TCP-80-Installation', 'Installation &amp; Commissioning', '100.00', 1, '0.00', '18.00', '118.00'),
(245, 137, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '950000.00', 1, '0.00', '5.00', '997500.00'),
(246, 137, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '120000.00', 1, '0.00', '18.00', '141600.00'),
(247, 137, 'TCP-120-Canopy', 'Canopy', '100000.00', 1, '0.00', '18.00', '118000.00'),
(248, 137, 'TCP-80-Installation', 'Installation & Commissioning ', '50000.00', 1, '0.00', '18.00', '59000.00'),
(252, 138, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '830000.00', 8, '0.00', '5.00', '6972000.00'),
(253, 139, 'TCP-060-D-0-002', 'DC Fast Charger Dual gun 60kW – Wi-Fi', '650000.00', 1, '3.00', '5.00', '662025.00'),
(254, 139, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '100000.00', 1, '0.00', '18.00', '118000.00'),
(255, 139, 'TCP-120-Canopy', 'Canopy', '120000.00', 1, '0.00', '18.00', '141600.00'),
(256, 139, 'TCP-80-Installation', 'Installation & Commissioning ', '50000.00', 1, '30.00', '18.00', '41300.00'),
(257, 139, 'TNEB Deposite Chargers', 'TNEB Deposite Chargers', '225000.00', 1, '0.00', '0.00', '225000.00'),
(260, 140, 'TCP-060-D-0-002', 'DC Fast Charger Dual gun 60kW – Wi-Fi', '650000.00', 1, '0.00', '5.00', '682500.00'),
(261, 140, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '70000.00', 1, '0.00', '18.00', '82600.00'),
(262, 140, 'TCP-80-Installation', 'Installation &amp; Commissioning', '30000.00', 1, '0.00', '18.00', '35400.00'),
(263, 140, 'TNEB Deposite Chargers', 'TNEB Deposite Chargers', '225000.00', 1, '0.00', '18.00', '265500.00'),
(264, 141, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '950000.00', 1, '0.00', '5.00', '997500.00'),
(265, 142, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '900000.00', 1, '0.00', '5.00', '945000.00'),
(266, 142, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera & Fire Extinguisher)', '120000.00', 1, '0.00', '18.00', '141600.00'),
(267, 142, 'TCP-120-Canopy', 'Canopy', '100000.00', 1, '0.00', '18.00', '118000.00'),
(268, 142, 'TCP-80-Installation', 'Installation & Commissioning ', '30000.00', 1, '0.00', '18.00', '35400.00'),
(269, 142, 'TNEB Deposite Chargers', 'TNEB Deposite Chargers', '368000.00', 1, '0.00', '0.00', '368000.00'),
(273, 144, 'TCP-060-D-0-002', 'DC Fast Charger Dual gun 60kW – Wi-Fi', '650000.00', 1, '0.00', '5.00', '682500.00'),
(274, 144, 'TCP-80-Installation', 'Installation &amp; Commissioning', '100000.00', 1, '0.00', '18.00', '118000.00'),
(275, 144, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '100000.00', 1, '0.00', '18.00', '118000.00'),
(276, 144, 'TCP-120-Canopy', 'Canopy', '100000.00', 1, '0.00', '18.00', '118000.00'),
(277, 145, 'TCP-060-D-0-002', 'DC Fast Charger Dual gun 60kW – Wi-Fi', '600000.00', 1, '0.00', '5.00', '630000.00'),
(278, 145, 'TCP-80-Installation', 'Installation & Commissioning ', '50000.00', 1, '0.00', '18.00', '59000.00'),
(279, 145, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera & Fire Extinguisher)', '70000.00', 1, '0.00', '18.00', '82600.00'),
(280, 145, 'TCP-120-Canopy', 'Canopy', '100000.00', 1, '0.00', '18.00', '118000.00'),
(284, 146, 'TCP-060-D-0-002', 'DC Fast Charger Dual gun 60kW – Wi-Fi', '630000.00', 1, '0.00', '5.00', '661500.00'),
(285, 146, 'TCP-80-Installation', 'Installation & Commissioning ', '50000.00', 1, '0.00', '18.00', '59000.00'),
(286, 146, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera & Fire Extinguisher)', '70000.00', 1, '0.00', '18.00', '82600.00'),
(287, 146, 'TCP-120-Canopy', 'Canopy', '100000.00', 1, '0.00', '18.00', '118000.00'),
(293, 148, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '850000.00', 1, '0.00', '5.00', '892500.00'),
(294, 149, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '850000.00', 1, '0.00', '5.00', '892500.00'),
(295, 150, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '850000.00', 1, '0.00', '5.00', '892500.00'),
(296, 150, 'TCP-120-Canopy', 'Canopy', '120000.00', 1, '0.00', '18.00', '141600.00'),
(297, 150, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '150000.00', 1, '0.00', '18.00', '177000.00'),
(298, 150, 'TCP-80-Installation', 'Installation &amp; Commissioning', '50000.00', 1, '0.00', '18.00', '59000.00'),
(302, 151, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '850000.00', 2, '0.00', '5.00', '1785000.00'),
(303, 151, 'TCP-120-Canopy', 'Canopy', '120000.00', 2, '0.00', '18.00', '283200.00'),
(304, 151, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '150000.00', 2, '0.00', '18.00', '354000.00'),
(305, 151, 'TCP-80-Installation', 'Installation &amp; Commissioning', '50000.00', 2, '0.00', '18.00', '118000.00'),
(315, 155, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '850000.00', 1, '0.00', '5.00', '892500.00'),
(316, 155, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '120000.00', 1, '0.00', '18.00', '141600.00'),
(317, 155, 'TCP-120-Canopy', 'Canopy', '100000.00', 1, '0.00', '18.00', '118000.00'),
(318, 155, 'TCP-80-Installation', 'Installation &amp; Commissioning', '50000.00', 1, '0.00', '18.00', '59000.00'),
(319, 155, 'TNEB Deposite Chargers', 'TNEB Deposite Chargers', '368000.00', 1, '0.00', '18.00', '434240.00'),
(320, 156, 'TCP-003-S-1-123', 'Sumo 3.3 – Wi-Fi + GSM + RFID', '7756.00', 5, '0.00', '18.00', '45760.40'),
(328, 164, 'TCP-003-S-1-001', 'Sumo 3.3 – GSM', '3000.00', 1, '0.00', '5.00', '3150.00'),
(329, 164, 'TCP-003-S-0-002', 'Type 2 personal charger 3.3 – Wi-Fi', '13000.00', 1, '1.00', '5.00', '13513.50'),
(330, 165, 'TCP-003-S-1-001', 'Sumo 3.3 – GSM', '3000.00', 1, '0.00', '5.00', '3150.00'),
(331, 165, 'TCP-003-S-0-002', 'Type 2 personal charger 3.3 – Wi-Fi', '13000.00', 1, '0.00', '5.00', '13650.00'),
(334, 167, 'TCP-060-D-0-002', 'DC Fast Charger Dual gun 60kW – Wi-Fi', '650000.00', 1, '4.61', '5.00', '651036.00'),
(335, 167, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '100000.00', 1, '0.00', '18.00', '118000.00'),
(336, 167, 'TCP-120-Canopy', 'Canopy', '40000.00', 1, '0.00', '18.00', '47200.00'),
(337, 167, 'TCP-80-Installation', 'Installation & Commissioning ', '50000.00', 1, '30.00', '18.00', '41300.00'),
(338, 167, 'TNEB Deposite Chargers', 'TNEB Deposite Chargers', '225000.00', 1, '0.00', '0.00', '225000.00'),
(341, 168, 'TCP-120-Franchise', '120kW DC Fast Charger (dual gun) - Franchise', '850000.00', 1, '0.00', '5.00', '892500.00'),
(342, 168, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '120000.00', 1, '0.00', '18.00', '141600.00'),
(343, 168, 'TCP-120-Canopy', 'Canopy', '100000.00', 1, '0.00', '18.00', '118000.00'),
(344, 168, 'TCP-80-Installation', 'Installation &amp; Commissioning', '50000.00', 1, '0.00', '18.00', '59000.00'),
(345, 168, 'TNEB Deposite Chargers', 'TNEB Deposite Chargers', '368000.00', 1, '0.00', '18.00', '434240.00'),
(346, 169, 'TCP-30-Franchise', '30kW DC Fast Charger - Franchise', '350000.00', 1, '0.00', '5.00', '367500.00'),
(347, 169, 'TCP-120-Canopy', 'Canopy', '20000.00', 1, '0.00', '18.00', '23600.00'),
(348, 169, 'TCP-80-Installation', 'Installation &amp; Commissioning', '150000.00', 1, '0.00', '18.00', '177000.00'),
(349, 169, 'TCP-80-Camera', 'Safety Equipment (Camera &amp; Fire Extinguisher)', '10000.00', 1, '0.00', '18.00', '11800.00'),
(350, 169, 'TransaportchargersDC', 'Transaport chargers DC', '100000.00', 1, '0.00', '18.00', '118000.00'),
(353, 172, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '850000.00', 1, '0.00', '5.00', '892500.00'),
(354, 173, '3KW Type 6 DC Fast Charger', '3KW Type 6 DC Fast Charger', '47000.00', 2, '0.00', '5.00', '98700.00'),
(355, 174, '3KW Type 6 DC Fast Charger', '3KW Type 6 DC Fast Charger', '47000.00', 2, '2.50', '5.00', '96232.00'),
(356, 175, 'TCP-060-D-0-002', 'DC Fast Charger Dual gun 60kW – Wi-Fi', '650000.00', 1, '4.61', '5.00', '651036.00'),
(357, 175, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '100000.00', 1, '0.00', '18.00', '118000.00'),
(358, 175, 'TCP-120-Canopy', 'Canopy', '40000.00', 1, '0.00', '18.00', '47200.00'),
(359, 175, 'TCP-80-Installation', 'Installation & Commissioning ', '50000.00', 1, '30.00', '18.00', '41300.00'),
(360, 175, 'TNEB Deposite Chargers', 'TNEB Deposite Chargers', '225000.00', 1, '0.00', '0.00', '225000.00'),
(363, 176, 'TCP-003-S-1-123', 'Sumo 3.3 – Wi-Fi + GSM + RFID', '4400.00', 5, '0.00', '5.00', '23100.00'),
(364, 176, 'Charge Card (RFID)', 'Charge Card (RFID)', '100.00', 5, '0.00', '18.00', '590.00'),
(365, 177, 'TCP-030-S-0-002', 'DC Fast Charger single gun 30kW – Wi-Fi', '380000.00', 1, '0.00', '5.00', '399000.00'),
(366, 178, 'TCP-030-S-0-002', 'DC Fast Charger single gun 30kW – Wi-Fi', '380000.00', 1, '0.00', '5.00', '399000.00'),
(367, 179, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '780000.00', 1, '0.00', '5.00', '819000.00'),
(368, 180, 'TCP-003-S-1-023', 'Sumo 3.3 – Wi-Fi + RFID', '5000.00', 2, '0.00', '5.00', '10500.00'),
(369, 181, 'TCP-060-S-0-002', 'DC Fast Charger single gun 60kW – Wi-Fi', '650000.00', 1, '0.00', '5.00', '682500.00'),
(370, 182, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '1100000.00', 1, '0.00', '5.00', '1155000.00'),
(371, 182, 'TCP-120-Canopy', 'Canopy', '250000.00', 1, '0.00', '18.00', '295000.00'),
(372, 182, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '200000.00', 1, '0.00', '18.00', '236000.00'),
(373, 182, 'TCP-80-Installation', 'Installation &amp; Commissioning', '100000.00', 1, '0.00', '18.00', '118000.00'),
(374, 183, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '1100000.00', 1, '0.00', '5.00', '1155000.00'),
(375, 183, 'TCP-120-Canopy', 'Canopy', '250000.00', 1, '0.00', '18.00', '295000.00'),
(376, 183, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '200000.00', 1, '0.00', '18.00', '236000.00'),
(377, 183, 'TCP-80-Installation', 'Installation &amp; Commissioning', '100000.00', 1, '0.00', '18.00', '118000.00'),
(381, 184, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '850000.00', 1, '0.00', '5.00', '892500.00'),
(382, 184, 'TCP-120-Canopy', 'Canopy', '150000.00', 1, '0.00', '18.00', '177000.00'),
(383, 184, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '150000.00', 1, '0.00', '18.00', '177000.00'),
(384, 184, 'TCP-80-Installation', 'Installation &amp; Commissioning', '50000.00', 1, '0.00', '18.00', '59000.00'),
(385, 185, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '950000.00', 1, '0.00', '5.00', '997500.00'),
(386, 185, 'TCP-003-S-1-002', 'Sumo 3.3 – Wi-Fi', '7000.00', 2, '0.00', '5.00', '14700.00'),
(387, 185, 'TCP-120-Canopy', 'Canopy', '120000.00', 1, '0.00', '18.00', '141600.00'),
(388, 185, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '120000.00', 1, '0.00', '18.00', '141600.00'),
(389, 185, 'TCP-80-Installation', 'Installation &amp; Commissioning', '50000.00', 1, '0.00', '18.00', '59000.00'),
(390, 185, 'TNEB Deposite Chargers', 'TNEB Deposite Chargers', '400000.00', 1, '0.00', '0.00', '400000.00'),
(391, 186, 'TCP-003-S-1-123', 'Sumo 3.3 – Wi-Fi + GSM + RFID', '4500.00', 1, '0.00', '5.00', '4725.00'),
(392, 187, 'TCP-120-D-0-002', 'DC Fast Charger Dual gun 120kW – Wi-Fi', '1400000.00', 1, '0.00', '5.00', '1470000.00'),
(393, 187, 'TCP-120-Canopy', 'Canopy', '200000.00', 1, '0.00', '18.00', '236000.00'),
(394, 187, 'TCP-80-DB Panel', 'DB Panel || Electrical Work || Safety Equipment (Camera &amp; Fire Extinguisher)', '180000.00', 1, '0.00', '18.00', '212400.00'),
(395, 187, 'TCP-80-Installation', 'Installation &amp; Commissioning', '75000.00', 1, '0.00', '18.00', '88500.00');

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int(11) NOT NULL,
  `parent_id` int(1) DEFAULT NULL,
  `client_id` varchar(50) DEFAULT NULL,
  `order_no` varchar(10) DEFAULT NULL,
  `order_date` timestamp NULL DEFAULT NULL,
  `quotation_no` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `valid_till` date NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `client_address` varchar(255) DEFAULT NULL,
  `salutation` varchar(100) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `introduction` text DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `order_status` char(1) NOT NULL DEFAULT 'N',
  `cp_status` char(1) DEFAULT 'N',
  `version_code` varchar(5) DEFAULT 'V1',
  `cms_id` varchar(50) DEFAULT NULL,
  `cpo_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `year` int(11) DEFAULT 1,
  `terms_conditions` longtext DEFAULT NULL,
  `term_id` int(11) DEFAULT NULL,
  `courier_service` varchar(100) DEFAULT NULL,
  `shipment_mode` varchar(20) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `parent_id`, `client_id`, `order_no`, `order_date`, `quotation_no`, `date`, `valid_till`, `client_name`, `client_address`, `salutation`, `subject`, `introduction`, `additional_notes`, `order_status`, `cp_status`, `version_code`, `cms_id`, `cpo_id`, `created_at`, `year`, `terms_conditions`, `term_id`, `courier_service`, `shipment_mode`, `tracking_number`) VALUES
(1, 5, '1', NULL, NULL, 'QUO-2026-01', '2026-02-02', '2026-03-04', 'Teja Sahithi', '21-160, Swatantra Nagar, Madhurawada,\nVishakapatnam\nVisakapatinam, AndhraPradesh\n530048', 'Mrs.Teja Sahithi,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '1', 'TTAA01', '2026-02-02 07:25:14', 1, NULL, NULL, NULL, NULL, NULL),
(2, 5, '3', NULL, NULL, 'QUO-2026-02', '2026-02-04', '2026-03-06', 'Saranga Raja', '168/53, Kuthukalavalasai, ChithraNagar,\nTenkasi, Tamilnadu\n627803', 'Mr.Saranga Raja,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '3', 'TTAA04', '2026-02-04 11:40:57', 1, NULL, NULL, NULL, NULL, NULL),
(3, 5, '4', NULL, NULL, 'QUO-2026-03', '2026-02-05', '2026-03-07', 'Sanjai Prasath', '38D, Perasiriyar Colony,Perianaicken Palayam,\nCoimbatore, TamilNadu\n641020', 'Mr.Sanjai Prasath,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '3', 'TTAA05', '2026-02-05 04:43:04', 1, NULL, NULL, NULL, NULL, NULL),
(4, 5, '5', NULL, NULL, 'QUO-2026-04', '2026-02-05', '2026-03-07', 'Amal', 'Ward No.15, Kuttipuzha building No.: 540, Door. no.7, P.O -Adimali\nAdimali, Kerala\n685561', 'Mr.Amal,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V1', '1', 'TTAA06', '2026-02-05 09:09:26', 1, NULL, NULL, NULL, NULL, NULL),
(5, 5, '6', NULL, NULL, 'QUO-2026-05', '2026-02-24', '2026-03-26', 'Varsha', '9A/B, SECTOR-H, GOVINDAPURA INDUSTRIAL AREA\nBhopal, Madhya Pradesh\n462023', 'Mr.Varsha,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'S', 'N', 'V1', '8', 'TMA-AA37', '2026-02-24 04:33:13', 1, NULL, NULL, NULL, NULL, NULL),
(6, 5, '7', NULL, NULL, 'QUO-2026-06', '2026-03-02', '2026-04-01', 'Magesh', 'RVR Complex, DasanikenPatti Village,\nSalem, TamilNadu\n636201', 'Mr.Magesh,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V1', '8', 'TTA-AA38', '2026-03-02 08:27:06', 1, NULL, NULL, NULL, NULL, NULL),
(7, 5, '7', NULL, NULL, 'QUO-2026-07', '2026-03-05', '2026-04-04', 'Magesh', 'RVR Complex, DasanikenPatti Village,\nSalem, TamilNadu\n636201', 'Mr.Magesh,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'TTA-AA38', '2026-03-05 06:19:56', 1, NULL, NULL, NULL, NULL, NULL),
(10, 5, '9', NULL, NULL, 'QUO-2026-09', '2026-03-06', '2026-04-05', 'mohan', 'kanyakumari\nmadurai, tamilnadu\n625018', 'Mr.mohan,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'S', 'N', 'V1', '8', 'TTA-AA38', '2026-03-06 07:41:47', 1, NULL, NULL, NULL, NULL, NULL),
(11, 5, '9', 'ORD-001', '2026-03-06 07:41:59', 'QUO-2026-09-V2', '2026-03-06', '2026-04-05', 'mohan', 'kanyakumari\nmadurai, tamilnadu\n625018', 'Mr.mohan,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V2', '8', 'TTA-AA38', '2026-03-06 07:41:59', 1, NULL, NULL, NULL, NULL, NULL),
(12, 5, '5', NULL, NULL, 'QUO-2026-10', '2026-03-09', '2026-04-08', 'Amal', 'Ward No.15, Kuttipuzha building No.: 540, Door. no.7, P.O -Adimali\nAdimali, Kerala\n685561', 'Mr.Amal,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V1', '2', '0', '2026-03-09 07:07:59', 1, NULL, NULL, NULL, NULL, NULL),
(13, 5, '6', 'ORD-013', '2026-03-09 07:50:52', 'QUO-2026-05-V2', '2026-02-24', '2026-03-26', 'Varsha', '9A/B, SECTOR-H, GOVINDAPURA INDUSTRIAL AREA\nBhopal, Madhya Pradesh\n462023', 'Mr.Varsha,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'Y', 'Y', 'V2', '8', 'TMA-AA37', '2026-03-09 07:50:52', 1, NULL, NULL, NULL, NULL, NULL),
(14, 5, '10', NULL, NULL, 'QUO-2026-11', '2026-03-09', '2026-04-08', 'Shahid', 'Ground Floor, Fathima Building, Thrissur - Kunnamkulam Rd, near Federal Bank, Kechery\nThrissur, Kerala\n680501', 'Mr.Shahid,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V1', '8', 'TKE-AA38', '2026-03-09 08:06:31', 1, NULL, NULL, NULL, NULL, NULL),
(15, 5, '8', NULL, NULL, 'QUO-2026-12', '2026-03-09', '2026-04-08', 'Rebolt Team', '25, Govindappa Rd, Gandhi Bazaar,\nBasavanagudi, Bengaluru\nBengaluru, Karnataka\n560004', 'Mr.Rebolt Team,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V1', '8', 'TKA-AA38', '2026-03-09 08:25:39', 1, NULL, NULL, NULL, NULL, NULL),
(16, 5, '11', 'ORD-002', NULL, 'QUO-2026-13', '2026-03-09', '2026-04-08', 'Santhosh', '10,madathu street,Mani nagarm,Aruppukottai\n10,madathu street,Mani nagarm,Aruppukottai\nAruppukottai, Tamil Nadu\n530048', 'Mr.Santhosh,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V1', '8', 'TIN-AA38', '2026-03-09 12:31:45', 1, NULL, NULL, NULL, NULL, NULL),
(19, 5, '13', NULL, NULL, 'QUO-2026-16', '2026-03-13', '2026-04-12', 'Laxmi Narayanan', 'Ground Floor No. 9, Madhavi Street, Karukku Main Road, Chennai\nChennai, TamilNadu\n600053', 'Mr.Laxmi Narayanan,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V1', '8', 'TTA-AA40', '2026-03-13 06:38:28', 1, NULL, NULL, NULL, NULL, NULL),
(27, 5, '19', 'ORD-012', NULL, 'QUO-2026-24', '2026-03-16', '2026-04-15', 'Subramanian Chokkalingam', '39/1, Muthuramalinga Street\nArasaradi\nMadurai, Tamilnadu\n625003', 'Mr.Subramanian Chokkalingam,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'Y', 'Y', 'V1', '8', 'TTA-AA40', '2026-03-16 07:29:55', 1, NULL, NULL, NULL, NULL, NULL),
(28, 5, '22', 'ORD-015', NULL, 'QUO-2026-25', '2026-03-17', '2026-04-16', 'Pandiyan Chinnasamy', '2538,TNHB Kudiyiruppu, Villapuram, Avaniyapuram\nMadurai, TamilNadu\n625012', 'Mr.Pandiyan Chinnasamy,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'Y', 'Y', 'V1', '8', 'TTA-AA40', '2026-03-17 06:48:34', 1, NULL, NULL, NULL, NULL, NULL),
(35, 5, '29', NULL, NULL, 'QUO-2026-29', '2026-03-26', '2026-04-25', 'Sahad', 'Sudheer Manzil, Chiruvallimukku,\nChirayinkeezhu P.O\nTiruvanthapuram, Kerala\n695304', 'Mr.Sahad,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'TKE-AA39', '2026-03-26 05:03:20', 1, NULL, NULL, NULL, NULL, NULL),
(39, 5, '31', 'ORD-017', NULL, 'QUO-2026-33', '2026-03-27', '2026-04-26', 'Vijeesh Vasu', 'Door No.2211,2/1149/I, Hilite Business Park, kozhikode,\nOlavanna, Kerala\n673019', 'Mr.Vijeesh Vasu,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'Y', 'Y', 'V1', '8', 'TKE-AA40', '2026-03-27 07:14:59', 1, NULL, NULL, NULL, NULL, NULL),
(42, 5, '34', NULL, NULL, 'QUO-2026-36', '2026-03-28', '2026-04-27', 'Vivek', 'Sai Aadiv Transport\nCoimbatore, TamilNadu\n641006', 'Mr.Vivek,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V1', '8', 'TTA-AA40', '2026-03-28 06:54:07', 1, NULL, NULL, NULL, NULL, NULL),
(43, 5, '34', NULL, NULL, 'QUO-2026-37', '2026-03-28', '2026-04-27', 'Vivek', 'Sai Aadiv Transport\nCoimbatore, TamilNadu\n641006', 'Mr.Vivek,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V1', '8', 'TTA-AA40', '2026-03-28 06:59:36', 1, NULL, NULL, NULL, NULL, NULL),
(44, 5, '34', NULL, NULL, 'QUO-2026-38', '2026-03-28', '2026-04-27', 'Vivek', 'Sai Aadiv Transport\nCoimbatore, TamilNadu\n641006', 'Mr.Vivek,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V1', '8', 'TTA-AA40', '2026-03-28 07:01:59', 1, NULL, NULL, NULL, NULL, NULL),
(72, 5, '61', NULL, NULL, 'QUO-2026-52', '2026-04-01', '2026-05-01', 'Er. M. Joseph Rathinaswamy', '36A, West Ponnagaram, 8th Street,\nMadurai, TamilNadu\n625016', 'Mr.Er. M. Joseph Rathinaswamy,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'TTA-AA40', '2026-04-01 06:18:57', 1, NULL, NULL, NULL, NULL, NULL),
(73, 5, '61', NULL, NULL, 'QUO-2026-53', '2026-04-01', '2026-05-01', 'Er. M. Joseph Rathinaswamy', '36A, West Ponnagaram, 8th Street,\nMadurai, TamilNadu\n625016', 'Mr.Er. M. Joseph Rathinaswamy,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'TTA-AA40', '2026-04-01 06:23:34', 1, NULL, NULL, NULL, NULL, NULL),
(77, 5, '62', NULL, NULL, 'QUO-2026-57', '2026-04-04', '2026-05-04', 'Vidit Gupta', 'Near Agarwal College,Agra Road,\nJaipur, Rajasthan\n302004', 'Mr.Vidit Gupta,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V1', '8', 'TRA-AA41', '2026-04-04 08:04:03', 1, NULL, NULL, NULL, NULL, NULL),
(78, 5, '64', NULL, NULL, 'QUO-2026-58', '2026-04-06', '2026-05-06', 'Syed', '2/206, West 2nd Street, Avudaiyar Kovil\nPudukottai, TamilNadu\n614618', 'Mr.Syed,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V1', '8', 'TTA-AA41', '2026-04-06 08:31:02', 1, NULL, NULL, NULL, NULL, NULL),
(79, 5, '64', NULL, NULL, 'QUO-2026-59', '2026-04-06', '2026-05-06', 'Syed', '2/206, West 2nd Street, Avudaiyar Kovil\nPudukottai, TamilNadu\n614618', 'Mr.Syed,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V1', '8', 'TTA-AA41', '2026-04-06 08:39:26', 1, NULL, NULL, NULL, NULL, NULL),
(80, 5, '61', 'ORD-021', NULL, 'QUO-2026-60', '2026-04-06', '2026-05-06', 'Er. M. Joseph Rathinaswamy', '36A, West Ponnagaram, 8th Street,\nMadurai, TamilNadu\n625016', 'Mr.Er. M. Joseph Rathinaswamy,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'Y', 'Y', 'V1', '8', 'TTN-AA42', '2026-04-06 09:30:48', 1, NULL, NULL, NULL, NULL, NULL),
(81, 5, '65', NULL, NULL, 'QUO-2026-61', '2026-04-06', '2026-05-06', 'RABIN', 'kl dfgdfg dfgdftdr fgdfgdfg\nklgdfgdfgdfgn dfgdfgdfgdftg\nMelarannor, Kerala\n900001', 'Mr.RABIN,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'Y', 'V1', '7', 'TKE-AA41', '2026-04-06 10:28:36', 1, NULL, NULL, NULL, NULL, NULL),
(82, 5, '66', NULL, NULL, 'QUO-2026-62', '2026-04-08', '2026-05-08', 'Ram Kumar', 'No 1, Sivaganga Rd, Veerapanjan,\nMadurai, TamilNadu\n625020', 'Mr.Ram Kumar,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'TTA-AA41', '2026-04-08 07:40:05', 1, NULL, NULL, NULL, NULL, NULL),
(83, 5, '66', NULL, NULL, 'QUO-2026-63', '2026-04-08', '2026-05-08', 'Ram Kumar', 'No 1, Sivaganga Rd, Veerapanjan,\nMadurai, TamilNadu\n625020', 'Mr.Ram Kumar,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'S', 'N', 'V1', '8', 'TTA-AA41', '2026-04-08 07:43:10', 1, NULL, NULL, NULL, NULL, NULL),
(84, 5, '66', 'ORD-028', '2026-05-02 07:18:36', 'QUO-2026-63-V2', '2026-04-08', '2026-05-08', 'Ram Kumar', 'No 1, Sivaganga Rd, Veerapanjan,\nMadurai, TamilNadu\n625020', 'Mr.Ram Kumar,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'Y', 'Y', 'V2', '8', 'TTA-AA41', '2026-04-09 04:59:44', 1, NULL, NULL, NULL, NULL, NULL),
(85, 5, '67', NULL, NULL, 'QUO-2026-64', '2026-04-09', '2026-05-09', 'Saran', 'Vembur\nThoothukudi, TamilNadu\n628905', 'Mr.Saran,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V1', '8', 'TTA-AA41', '2026-04-09 08:12:58', 1, NULL, NULL, NULL, NULL, NULL),
(86, 5, '70', NULL, NULL, 'QUO-2026-65', '2026-04-16', '2026-05-16', 'Shubham', 'House No.50, Ward no.2, Bada Pool pass, Nasrullaganj\nSehore, Madhya Pradesh\n466331', 'Mr.Shubham,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V1', '8', 'TMA-AA41', '2026-04-16 06:17:19', 1, NULL, NULL, NULL, NULL, NULL),
(87, 5, '75', 'ORD-024', NULL, 'QUO-2026-66', '2026-04-18', '2026-05-18', 'Sathish kumar', 'Plot S-1/Pt.-2, SIPCOT Engineering SEZ, SIPCOT\nEngineering SEZ Road, Perundurai Industrial Park, Perundurai\nErode, Tamil Nadu\n638052', 'Mr.Sathish kumar,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'Y', 'Y', 'V1', '8', 'TTA-AA41', '2026-04-18 11:26:18', 1, NULL, NULL, NULL, NULL, NULL),
(88, 5, '76', 'ORD-023', NULL, 'QUO-2026-67', '2026-04-18', '2026-05-18', 'Saravana kumar', '5/208-B, Trichy Main Road, Marachipatti, Elurpatti  Post\nThottiyam\nTiruchirappalli, Tamil Nadu\n621215', 'Mr.Saravana kumar,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'Y', 'Y', 'V1', '8', 'TTN-AA44', '2026-04-18 11:45:13', 1, NULL, NULL, NULL, NULL, NULL),
(89, 5, '74', NULL, NULL, 'QUO-2026-68', '2026-04-20', '2026-05-20', 'Shailendra', 'Lucknow\nLucknow, UttarPradesh\n226023', 'Mr.Shailendra,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost &amp; Charger Management Software(CMS) of ₹50,000 plus 18% GST,Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, The', 'N', 'N', 'V1', '8', 'TUT-AA41', '2026-04-20 06:10:23', 1, NULL, NULL, NULL, NULL, NULL),
(90, 5, '61', NULL, NULL, 'QUO-2026-69', '2026-04-20', '2026-05-20', 'Er. M. Joseph Rathinaswamy', '36A, West Ponnagaram, 8th Street,\nMadurai, TamilNadu\n625016', 'Mr.Er. M. Joseph Rathinaswamy,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'TTA-AA40', '2026-04-20 06:16:28', 1, NULL, NULL, NULL, NULL, NULL),
(91, 5, '140', NULL, NULL, 'QUO-2026-70', '2026-04-20', '2026-05-20', 'Rajasekar', 'Karaikal\nKaraikal, TamilNadu\n609602', 'Mr.Rajasekar,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'S', 'N', 'V1', '8', 'TTA-AA41', '2026-04-20 06:20:34', 1, NULL, NULL, NULL, NULL, NULL),
(92, 5, '140', NULL, '2026-04-20 06:59:57', 'QUO-2026-70-V2', '2026-04-20', '2026-05-20', 'Rajasekar', 'Karaikal\r\nKaraikal, TamilNadu\r\n609602', 'Mr. Rajasekar,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'S', 'N', 'V2', '8', 'TTA-AA41', '2026-04-20 06:59:57', 1, NULL, NULL, NULL, NULL, NULL),
(93, 5, '140', NULL, '2026-04-20 07:01:18', 'QUO-2026-70-V3', '2026-04-20', '2026-05-20', 'Mr. Rajasekar', 'Karaikal\r\nKaraikal, TamilNadu\r\n609602', 'Mr. Rajasekar,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V3', '8', 'TTA-AA41', '2026-04-20 07:01:18', 1, NULL, NULL, NULL, NULL, NULL),
(95, 5, '65', NULL, NULL, 'QUO-2026-71', '2026-04-21', '2026-05-21', 'RABIN', 'kl dfgdfg dfgdftdr fgdfgdfg\nklgdfgdfgdfgn dfgdfgdfgdftg\nMelarannor, Kerala\n900001', 'Mr.RABIN,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'Y', 'V1', '7', 'TKE-AA41', '2026-04-21 06:50:04', 1, NULL, NULL, NULL, NULL, NULL),
(96, 5, '114', 'ORD-026', '2026-04-22 06:20:54', 'QUO-2026-72', '2026-04-22', '2026-05-22', 'Prakash dhage', 'SN 06, Hissa No 3A, Deshmukh Nagar, NDA Road Shivane, Pune 411023, India, Pune, Maharashtra\nPune, Maharashtra\n900001', 'Dear Prakash dhage,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'Y', 'Y', 'V1', 'tucker', 'WMH-AA33', '2026-04-22 05:51:39', 1, NULL, NULL, NULL, NULL, NULL),
(102, 5, '146', 'ORD-027', '2026-05-02 07:15:53', 'QUO-2026-77', '2026-04-24', '2026-05-24', 'Aravind', '123/18 6th Cross CV raman nagar\nBangalore, Karnataka\n560093', 'Mr.Aravind,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'Y', 'Y', 'V1', '8', 'TKA-AA42', '2026-04-24 08:28:17', 1, NULL, NULL, NULL, NULL, NULL),
(107, 5, '149', NULL, NULL, 'QUO-2026-81', '2026-05-04', '2026-06-03', 'Bhaskar Kumar', 'Plot No.F/8 ,Atwal Nagar, 80ft Road\nKota, Rajasthan\n324001', 'Mr.Bhaskar Kumar,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V1', '8', 'TRA-AA45', '2026-05-04 04:35:59', 1, NULL, NULL, NULL, NULL, NULL),
(108, 5, '150', NULL, NULL, 'QUO-2026-82', '2026-05-04', '2026-06-03', 'P.A Praveen', '82,East 3rd Street, TRV Nagar, Arupukottai\nVirudhunagar, TamilNadu\n626101', 'Mr.P.A Praveen,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'S', 'N', 'V1', '8', 'TTA-AA45', '2026-05-04 07:47:12', 1, NULL, NULL, NULL, NULL, NULL),
(109, 5, '146', NULL, NULL, 'QUO-2026-83', '2026-05-06', '2026-06-05', 'Aravind', '123/18 6th Cross CV raman nagar\nBangalore, Karnataka\n560093', 'Mr.Aravind,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'S', 'N', 'V1', '8', 'TKA-AA42', '2026-05-06 05:05:23', 1, NULL, NULL, NULL, NULL, NULL),
(110, 5, '151', NULL, NULL, 'QUO-2026-84', '2026-05-06', '2026-06-05', 'Vithal Rao Kanchi', 'Kodangal\nMahabub, Telagana\n509338', 'Mr.Vithal Rao Kanchi,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'S', 'N', 'V1', '8', 'TTE-AA45', '2026-05-06 06:17:48', 1, NULL, NULL, NULL, NULL, NULL),
(111, 5, '146', NULL, '2026-05-06 07:20:34', 'QUO-2026-83-V2', '2026-05-06', '2026-06-05', 'Aravind', '123/18 6th Cross CV raman nagar\nBangalore, Karnataka\n560093', 'Mr.Aravind,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'S', 'N', 'V2', '8', 'TKA-AA42', '2026-05-06 07:20:34', 1, NULL, NULL, NULL, NULL, NULL),
(112, 5, '146', NULL, '2026-05-06 07:28:33', 'QUO-2026-83-V3', '2026-05-06', '2026-06-05', 'Aravind', '123/18 6th Cross CV raman nagar\nBangalore, Karnataka\n560093', 'Mr.Aravind,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'S', 'N', 'V3', '8', 'TKA-AA42', '2026-05-06 07:28:33', 1, NULL, NULL, NULL, NULL, NULL),
(113, 5, '151', NULL, '2026-05-06 07:29:48', 'QUO-2026-84-V2', '2026-05-06', '2026-06-05', 'Vithal Rao Kanchi', 'Kodangal\nMahabub, Telagana\n509338', 'Mr.Vithal Rao Kanchi,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'S', 'N', 'V2', '8', 'TTE-AA45', '2026-05-06 07:29:48', 1, NULL, NULL, NULL, NULL, NULL),
(114, 5, '151', NULL, '2026-05-06 07:32:06', 'QUO-2026-84-V3', '2026-05-06', '2026-06-05', 'Vithal Rao Kanchi', 'Kodangal\nMahabub, Telagana\n509338', 'Mr.Vithal Rao Kanchi,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V3', '8', 'TTE-AA45', '2026-05-06 07:32:06', 1, NULL, NULL, NULL, NULL, NULL),
(115, 5, '146', NULL, '2026-05-06 07:37:57', 'QUO-2026-83-V4', '2026-05-06', '2026-06-05', 'Aravind', '123/18 6th Cross CV raman nagar\nBangalore, Karnataka\n560093', 'Mr.Aravind,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'S', 'N', 'V4', '8', 'TKA-AA42', '2026-05-06 07:37:57', 1, NULL, NULL, NULL, NULL, NULL),
(116, 5, '146', NULL, '2026-05-06 07:38:21', 'QUO-2026-83-V5', '2026-05-06', '2026-06-05', 'Aravind', '123/18 6th Cross CV raman nagar\nBangalore, Karnataka\n560093', 'Mr.Aravind,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'Y', 'N', 'V5', '8', 'TKA-AA42', '2026-05-06 07:38:21', 1, NULL, NULL, NULL, NULL, NULL),
(117, 5, '146', 'ORD-029', '2026-05-06 07:48:24', 'QUO-2026-83-V6', '2026-05-06', '2026-06-05', 'Aravind', '123/18 6th Cross CV raman nagar\nBangalore, Karnataka\n560093', 'Mr.Aravind,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'Y', 'Y', 'V6', '8', 'TKA-AA42', '2026-05-06 07:38:29', 1, NULL, NULL, NULL, NULL, NULL),
(119, 5, '154', NULL, NULL, 'QUO-2026-86', '2026-05-07', '2026-06-06', 'Fusiontek', 'Q11,3rd main kssidc industrial estate, veersandra, 2nd stage, electronic City phase 2\nBangalore, Karnataka\n560100', 'Mr.Fusiontek,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'S', 'N', 'V1', '8', 'TKA-AA45', '2026-05-07 04:45:10', 1, NULL, NULL, NULL, NULL, NULL),
(120, 5, '154', NULL, '2026-05-07 04:45:26', 'QUO-2026-86-V2', '2026-05-07', '2026-06-06', 'Fusiontek', 'Q11,3rd main kssidc industrial estate, veersandra, 2nd stage, electronic City phase 2\nBangalore, Karnataka\n560100', 'Mr.Fusiontek,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'S', 'N', 'V2', '8', 'TKA-AA45', '2026-05-07 04:45:26', 1, NULL, NULL, NULL, NULL, NULL),
(121, 5, '6', 'ORD-033', '2026-05-20 05:04:20', 'QUO-2026-87', '2026-05-08', '2026-06-07', 'Varsha', '9A/B, SECTOR-H, GOVINDAPURA INDUSTRIAL AREA\nBhopal, Madhya Pradesh\n462023', 'Mr.Varsha,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'Y', 'Y', 'V1', '7', 'TMA-AA37', '2026-05-08 05:21:14', 1, NULL, NULL, NULL, NULL, NULL),
(122, 5, '155', NULL, NULL, 'QUO-2026-88', '2026-05-08', '2026-06-07', 'Laxmanan', 'Rayarpalayam, Tiruchengode\nNamakkal, TamilNadu\n641659', 'Mr.Laxmanan,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'TTA-AA45', '2026-05-08 07:12:56', 1, NULL, NULL, NULL, NULL, NULL),
(123, 4, '158', NULL, NULL, 'QUO-2026-89', '2026-05-08', '2026-06-07', 'johnson jacob', 'asset homes\nernakulam, kerala\n682304', 'Mr.johnson jacob,', 'Quotation for Ac charger-Reg', 'We hereby give our best quote based on our understanding and as per technical specification provided as follows', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'Y', 'N', 'V1', '8', 'TKE-AA45', '2026-05-08 10:21:26', 1, NULL, NULL, NULL, NULL, NULL),
(124, 5, '161', NULL, NULL, 'QUO-2026-90', '2026-05-09', '2026-06-08', 'Valluru Satwik Reddy', '39/631-9, Aravind Nagar, Patel road-2\nKadappa, Andhra Pradesh\n516001', 'Mr.Valluru Satwik Reddy,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'S', 'N', 'V1', '8', 'TAN-AA45', '2026-05-09 04:46:37', 1, NULL, NULL, NULL, NULL, NULL),
(125, 5, '161', NULL, NULL, 'QUO-2026-91', '2026-05-09', '2026-06-08', 'Valluru Satwik Reddy', '39/631-9, Aravind Nagar, Patel road-2\nKadappa, Andhra Pradesh\n516001', 'Mr.Valluru Satwik Reddy,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'S', 'N', 'V1', '8', 'TAN-AA45', '2026-05-09 04:55:27', 1, NULL, NULL, NULL, NULL, NULL),
(126, 5, '165', NULL, NULL, 'QUO-2026-92', '2026-05-12', '2026-06-11', 'Karuppaiyah', '159/C1/1, Kamarajar salai\nNear Nirmala school\nMadurai, Tamil Nadu\n625018', 'Mr.Karuppaiyah,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'S', 'N', 'V1', '8', 'TTA-AA45', '2026-05-12 03:47:34', 1, NULL, NULL, NULL, NULL, NULL),
(127, 5, '165', NULL, '2026-05-12 03:48:51', 'QUO-2026-92-V2', '2026-05-12', '2026-06-11', 'Karuppaiyah', '159/C1/1, Kamarajar salai\nNear Nirmala school\nMadurai, Tamil Nadu\n625018', 'Mr.Karuppaiyah,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'S', 'N', 'V2', '8', 'TTA-AA45', '2026-05-12 03:48:51', 1, NULL, NULL, NULL, NULL, NULL),
(128, 5, '165', NULL, '2026-05-12 03:49:37', 'QUO-2026-92-V3', '2026-05-12', '2026-06-11', 'Karuppaiya', '159/C1/1, Kamarajar salai\r\nNear Nirmala school\r\nMadurai, Tamil Nadu\r\n625018', 'Mr.Karuppaiyah,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'S', 'N', 'V3', '8', 'TTA-AA45', '2026-05-12 03:49:37', 1, NULL, NULL, NULL, NULL, NULL),
(130, 5, '166', NULL, NULL, 'QUO-2026-93', '2026-05-12', '2026-06-11', 'S. Venkateswara Pandiyan', '33,Madha Nagar,2nd Street, Govt School opposite,\nIllupakkudi\nKaraikudi, TamilNadu\n630202', 'Mr.S. Venkateswara Pandiyan,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'TTA-AA45', '2026-05-12 05:02:20', 1, NULL, NULL, NULL, NULL, NULL),
(131, 5, '166', NULL, NULL, 'QUO-2026-94', '2026-05-12', '2026-06-11', 'S. Venkateswara Pandiyan', '33,Madha Nagar,2nd Street, Govt School opposite,\nIllupakkudi\nKaraikudi, TamilNadu\n630202', 'Mr.S. Venkateswara Pandiyan,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'S', 'N', 'V1', '8', 'TTA-AA45', '2026-05-12 05:09:25', 1, NULL, NULL, NULL, NULL, NULL),
(132, 5, '87', 'ORD-032', '2026-05-20 05:00:24', 'QUO-2026-95', '2026-05-12', '2026-06-11', 'Manumurali', '\nAlappuzha, Kerala\n600002', 'Dear Manumurali,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'Y', 'Y', 'V1', 'zeed4', 'TKL-AA16', '2026-05-12 07:15:35', 1, NULL, NULL, NULL, NULL, NULL),
(133, 5, '167', NULL, NULL, 'QUO-2026-96', '2026-05-13', '2026-06-12', 'Khush Chandawat', 'No. 10/4, Chandawat, Devanathchar Road, Chamarajpet\nBangalore, Karnataka\n560018', 'Mr.Khush Chandawat,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'S', 'N', 'V1', '5', 'TKA-AA46', '2026-05-13 05:06:06', 1, NULL, NULL, NULL, NULL, NULL),
(134, 5, '168', NULL, NULL, 'QUO-2026-97', '2026-05-13', '2026-06-12', 'Ramalingam H', '6/35, College Road, 1st cross Street\nTiruppur, TamilNadu\n641602', 'Mr.Ramalingam H,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'S', 'N', 'V1', '8', 'TTA-AA46', '2026-05-13 06:15:16', 1, NULL, NULL, NULL, NULL, NULL),
(135, 5, '168', NULL, '2026-05-13 06:15:38', 'QUO-2026-97-V2', '2026-05-13', '2026-06-12', 'Ramalingam H', '6/35, College Road, 1st cross Street\nTiruppur, TamilNadu\n641602', 'Mr.Ramalingam H,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V2', '8', 'TTA-AA46', '2026-05-13 06:15:38', 1, NULL, NULL, NULL, NULL, NULL),
(136, 5, '168', NULL, NULL, 'QUO-2026-98', '2026-05-13', '2026-06-12', 'Ramalingam H', '6/35, College Road, 1st cross Street\nTiruppur, TamilNadu\n641602', 'Mr.Ramalingam H,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'S', 'N', 'V1', '8', 'TTA-AA46', '2026-05-13 06:19:00', 1, NULL, NULL, NULL, NULL, NULL),
(137, 5, '168', NULL, '2026-05-13 06:22:06', 'QUO-2026-98-V2', '2026-05-13', '2026-06-12', 'Ramalingam H', '6/35, College Road, 1st cross Street\nTiruppur, TamilNadu\n641602', 'Mr.Ramalingam H,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V2', '8', 'TTA-AA46', '2026-05-13 06:22:06', 1, NULL, NULL, NULL, NULL, NULL),
(138, 5, '169', 'ORD-031', '2026-05-13 08:27:12', 'QUO-2026-99', '2026-05-13', '2026-06-12', 'SushilKumar Tulsiram Sarda', 'C-1, G1-2, Gangatri Apartment, Ring Road,\nIndore, Madhya Pradesh\n452018', 'Mr.SushilKumar Tulsiram Sarda,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'Razorpay deducted amount Rs. 1.50', 'Y', 'Y', 'V1', '8', 'TMA-AA46', '2026-05-13 08:03:46', 10, NULL, NULL, NULL, NULL, NULL),
(139, 5, '150', NULL, '2026-05-14 06:40:16', 'QUO-2026-82-V2', '2026-05-04', '2026-06-03', 'P.A Praveen', '82,East 3rd Street, TRV Nagar, Arupukottai\nVirudhunagar, TamilNadu\n626101', 'Mr.P.A Praveen,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'S', 'N', 'V2', '8', 'TTA-AA45', '2026-05-14 06:40:16', 1, NULL, NULL, NULL, NULL, NULL),
(140, 5, '170', NULL, NULL, 'QUO-2026-100', '2026-05-14', '2026-06-13', 'Selvaraj', 'No.7, Bharathi Nagar, near vision school, MC Road,\nThanjavur, TamilNadu\n613010', 'Mr.Selvaraj,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'TTA-AA46', '2026-05-14 06:55:27', 1, NULL, NULL, NULL, NULL, NULL),
(141, 5, '171', NULL, NULL, 'QUO-2026-101', '2026-05-14', '2026-06-13', 'KM Construction', 'No.10, North gate ss colony,\nMadurai, TamilNadu\n625019', 'Mr.KM Construction,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'TTA-AA46', '2026-05-14 07:50:55', 1, NULL, NULL, NULL, NULL, NULL),
(142, 5, '166', NULL, '2026-05-14 08:12:33', 'QUO-2026-94-V2', '2026-05-12', '2026-06-11', 'S. Venkateswara Pandiyan', '33,Madha Nagar,2nd Street, Govt School opposite,\nIllupakkudi\nKaraikudi, TamilNadu\n630202', 'Mr.S. Venkateswara Pandiyan,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V2', '8', 'TTA-AA45', '2026-05-14 08:12:33', 1, NULL, NULL, NULL, NULL, NULL),
(144, 5, '172', NULL, NULL, 'QUO-2026-103', '2026-05-15', '2026-06-14', 'Bilal', 'C-152 Ekta vihar South\nRampur Dauhra\nMoradabad, Uttar Pradesh\n244001', 'Mr.Bilal,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'S', 'N', 'V1', '8', 'TUT-AA46', '2026-05-15 08:50:54', 1, NULL, NULL, NULL, NULL, NULL),
(145, 5, '172', NULL, '2026-05-15 08:53:44', 'QUO-2026-103-V2', '2026-05-15', '2026-06-14', 'Bilal', 'C-152 Ekta vihar South\nRampur Dauhra\nMoradabad, Uttar Pradesh\n244001', 'Mr.Bilal,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'S', 'N', 'V2', '8', 'TUT-AA46', '2026-05-15 08:53:44', 1, NULL, NULL, NULL, NULL, NULL),
(146, 5, '172', NULL, '2026-05-15 08:56:59', 'QUO-2026-103-V3', '2026-05-15', '2026-06-14', 'Bilal', 'C-152 Ekta vihar South\nRampur Dauhra\nMoradabad, Uttar Pradesh\n244001', 'Mr.Bilal,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V3', '8', 'TUT-AA46', '2026-05-15 08:56:59', 1, NULL, NULL, NULL, NULL, NULL),
(148, 5, '154', NULL, '2026-05-18 07:25:15', 'QUO-2026-86-V3', '2026-05-07', '2026-06-06', 'Fusiontek', 'Q11,3rd main kssidc industrial estate, veersandra, 2nd stage, electronic City phase 2\nBangalore, Karnataka\n560100', 'Mr.Fusiontek,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V3', '8', 'TKA-AA45', '2026-05-18 07:25:15', 1, NULL, NULL, NULL, NULL, NULL),
(149, 5, '154', NULL, '2026-05-18 07:25:26', 'QUO-2026-86-V3', '2026-05-07', '2026-06-06', 'Fusiontek', 'Q11,3rd main kssidc industrial estate, veersandra, 2nd stage, electronic City phase 2\nBangalore, Karnataka\n560100', 'Mr.Fusiontek,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V3', '8', 'TKA-AA45', '2026-05-18 07:25:26', 1, NULL, NULL, NULL, NULL, NULL),
(150, 5, '161', NULL, '2026-05-18 07:26:28', 'QUO-2026-91-V2', '2026-05-09', '2026-06-08', 'Valluru Satwik Reddy', '39/631-9, Aravind Nagar, Patel road-2\nKadappa, Andhra Pradesh\n516001', 'Mr.Valluru Satwik Reddy,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V2', '8', 'TAN-AA45', '2026-05-18 07:26:28', 1, NULL, NULL, NULL, NULL, NULL),
(151, 5, '161', NULL, '2026-05-18 07:27:38', 'QUO-2026-90-V2', '2026-05-09', '2026-06-08', 'Valluru Satwik Reddy', '39/631-9, Aravind Nagar, Patel road-2\nKadappa, Andhra Pradesh\n516001', 'Mr.Valluru Satwik Reddy,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V2', '8', 'TAN-AA45', '2026-05-18 07:27:38', 1, NULL, NULL, NULL, NULL, NULL),
(155, 5, '177', NULL, NULL, 'QUO-2026-104', '2026-05-19', '2026-06-18', 'Sree', 'No.4/2, 2nd Floor, Muthukrishna Flat, Kalaingnar street,Avadi,\nChennai, TamilNadu\n600054', 'Mr.Sree,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'TTA-AA46', '2026-05-19 06:00:48', 1, NULL, NULL, NULL, NULL, NULL),
(164, 5, '181', NULL, NULL, 'QUO-2026-107', '2026-05-20', '2026-06-19', 'Anand', 'Plot No. 5/89-3, 3A, Ward &quot;C Block 19, Chettiyar Park Road, MM Street\nKodaikanal, TamilNadu\n624101', 'Mr.Anand,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'S', 'N', 'V1', '8', 'TTA-AA47', '2026-05-20 04:37:48', 1, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `quotations` (`id`, `parent_id`, `client_id`, `order_no`, `order_date`, `quotation_no`, `date`, `valid_till`, `client_name`, `client_address`, `salutation`, `subject`, `introduction`, `additional_notes`, `order_status`, `cp_status`, `version_code`, `cms_id`, `cpo_id`, `created_at`, `year`, `terms_conditions`, `term_id`, `courier_service`, `shipment_mode`, `tracking_number`) VALUES
(165, 5, '181', NULL, '2026-05-20 05:13:04', 'QUO-2026-107-V2', '2026-05-20', '2026-06-19', 'Anand', 'Plot No. 5/89-3, 3A, Ward &quot;C Block 19, Chettiyar Park Road, MM Street\nKodaikanal, TamilNadu\n624101', 'Mr.Anand,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V2', '8', 'TTA-AA47', '2026-05-20 05:13:04', 1, NULL, NULL, NULL, NULL, NULL),
(167, 5, '150', NULL, '2026-05-20 05:21:58', 'QUO-2026-82-V3', '2026-05-04', '2026-06-03', 'P.A Praveen', '82,East 3rd Street, TRV Nagar, Arupukottai\nVirudhunagar, TamilNadu\n626101', 'Mr.P.A Praveen,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'S', 'N', 'V3', '8', 'TTA-AA45', '2026-05-20 05:21:58', 1, NULL, NULL, NULL, NULL, NULL),
(168, 5, '182', NULL, NULL, 'QUO-2026-109', '2026-05-20', '2026-06-19', 'Dhanasekar', '2/429,pallivasal street, Gandhi nagar, Batlagundu\nDindigul, TamilNadu\n624202', 'Mr.Dhanasekar,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'TTA-AA47', '2026-05-20 07:53:58', 1, NULL, NULL, NULL, NULL, NULL),
(169, 4, '183', NULL, NULL, 'QUO-2026-110', '2026-05-20', '2026-06-19', 'kuruvila george', 'chakuvalli bharanikavu\nkollam, keraka\n690522', 'Mr.kuruvila george,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V1', '8', 'TKE-AA47', '2026-05-21 05:52:24', 1, NULL, NULL, NULL, NULL, NULL),
(172, 5, '185', NULL, NULL, 'QUO-2026-113', '2026-05-22', '2026-06-21', 'N. Venkatachalam', '18B Natham Road\nDindigul, TamilNadu\n624003', 'Mr.N. Venkatachalam,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'TTA-AA47', '2026-05-22 06:53:18', 10, NULL, NULL, NULL, NULL, NULL),
(173, 5, '167', NULL, '2026-05-25 07:34:06', 'QUO-2026-96-V2', '2026-05-13', '2026-06-12', 'Khush Chandawat', 'No. 10/4, Chandawat, Devanathchar Road, Chamarajpet\nBangalore, Karnataka\n560018', 'Mr.Khush Chandawat,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'S', 'N', 'V2', '5', 'TKA-AA46', '2026-05-25 07:34:06', 1, NULL, NULL, NULL, NULL, NULL),
(174, 5, '167', NULL, '2026-05-25 07:38:13', 'QUO-2026-96-V3', '2026-05-13', '2026-06-12', 'Khush Chandawat', 'No. 10/4, Chandawat, Devanathchar Road, Chamarajpet\nBangalore, Karnataka\n560018', 'Mr.Khush Chandawat,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.', 'N', 'N', 'V3', '5', 'TKA-AA46', '2026-05-25 07:38:13', 1, NULL, NULL, NULL, NULL, NULL),
(175, 5, '150', 'ORD-034', '2026-05-30 05:07:16', 'QUO-2026-82-V4', '2026-05-04', '2026-06-03', 'P.A Praveen', '82,East 3rd Street, TRV Nagar, Arupukottai\r\nVirudhunagar, TamilNadu\r\n626101', 'Mr.P.A Praveen,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', 'Warranty- 10 Years\r\nRazorpay dedcution- Rs. 1 Rs. 50 paise per unit', 'Y', 'Y', 'V4', '8', 'TTA-AA45', '2026-05-30 04:57:53', 1, NULL, NULL, NULL, NULL, NULL),
(176, 5, '114', NULL, NULL, 'QUO-2026-114', '2026-06-01', '2026-07-01', 'Prakash dhage', 'SN 06, Hissa No 3A, Deshmukh Nagar, NDA Road Shivane, Pune 411023, India, Pune, Maharashtra\nPune, Maharashtra\n900001', 'Mr.Prakash dhage,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'WMH-AA33', '2026-06-01 06:10:23', 1, NULL, NULL, NULL, NULL, NULL),
(177, 5, '101', NULL, NULL, 'QUO-2026-115', '2026-06-01', '2026-07-01', 'Rajaji', '892, N 2nd St, Wadair santha, Jeeva Nagar, Near 100mt rani hosipatal, Pudukkottai, Tamilnadu\n892, N 2nd St, Wadair santha, Jeeva Nagar, Near 100mt rani hosipatal, Pudukkottai, Tamilnadu\nPudukkottai, Tamilnadu\n600002', 'Dear Rajaji,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', 'tucker', 'TTN-AA08', '2026-06-01 06:14:21', 1, NULL, NULL, NULL, NULL, NULL),
(178, 5, '101', NULL, NULL, 'QUO-2026-116', '2026-06-01', '2026-07-01', 'Rajaji', '892, N 2nd St, Wadair santha, Jeeva Nagar, Near 100mt rani hosipatal, Pudukkottai, Tamilnadu\n892, N 2nd St, Wadair santha, Jeeva Nagar, Near 100mt rani hosipatal, Pudukkottai, Tamilnadu\nPudukkottai, Tamilnadu\n600002', 'Dear Rajaji,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', 'tucker', 'TTN-AA08', '2026-06-01 06:14:21', 1, NULL, NULL, NULL, NULL, NULL),
(179, 5, '116', 'ORD-035', '2026-06-03 08:11:47', 'QUO-2026-117', '2026-06-03', '2026-07-03', 'Madhavendra', 'Sarai Sahjadi, Banthara Bazar, Near Ramada Plaza, Kanpur Road\nMadura\nLucknow, Uttar Pradesh\n226401', 'Mr.Madhavendra,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'D', 'Y', 'V1', '4', 'WUP-AA27', '2026-06-03 08:08:51', 1, NULL, NULL, 'DTDC', 'Air', 'DAW23432432SDE'),
(180, 5, '186', 'ORD-036', '2026-06-09 05:59:13', 'QUO-2026-118', '2026-06-05', '2026-07-05', 'Rajalakshmi Ponnuswami', 'Max Woods, Flat No.27, Kilakuyilkudi, Thattanur\nMadurai, TamilNadu\n625019', 'Mr.Rajalakshmi Ponnuswami,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'Y', 'Y', 'V1', '8', 'TTA-AA45', '2026-06-05 04:45:33', 1, NULL, NULL, NULL, NULL, NULL),
(181, 5, '186', NULL, NULL, 'QUO-2026-119', '2026-06-05', '2026-07-05', 'Rajalakshmi Ponnuswami', 'Max Woods, Flat No.27, Kilakuyilkudi, Thattanur\nMadurai, TamilNadu\n625019', 'Mr.Rajalakshmi Ponnuswami,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'TTA-AA45', '2026-06-05 08:21:45', 1, NULL, NULL, NULL, NULL, NULL),
(182, 5, '188', NULL, NULL, 'QUO-2026-120', '2026-06-08', '2026-07-08', 'Karthi', 'Bhavani, Lakshmi Nagar\nErode, TamilNadu\n638052', 'Mr.Karthi,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'S', 'N', 'V1', '8', 'TTA-AA45', '2026-06-08 10:06:27', 1, 'Prices quoted are exclusive of GST unless otherwise specified. Quotation valid for 30 days.\r\n\r\n70% advance & 30% before dispatch. Dispatch after full payment only.\r\n\r\nSupply includes EV charger as per quotation. Installation and accessories included\r\n\r\nDelivery: DC Fast Charger – 3–4 weeks | AC Charger – 1–2 weeks from order confirmation and advance payment.\r\n\r\n10 year warranty against manufacturing defects only.\r\n\r\nAMC & Support: Annual Maintenance Contract (AMC) available after the warranty period.\r\n\r\nOrders once confirmed cannot be cancelled or returned.\r\n\r\nLiability limited to invoice value.\r\n\r\nGoverned by Indian law. Jurisdiction: Madurai, Tamil Nadu.', NULL, NULL, NULL, NULL),
(183, 5, '188', NULL, '2026-06-08 10:18:17', 'QUO-2026-120-V2', '2026-06-08', '2026-07-08', 'Karthi', 'Bhavani, Lakshmi Nagar\nErode, TamilNadu\n638052', 'Mr.Karthi,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V2', '8', 'TTA-AA45', '2026-06-08 10:18:17', 1, NULL, NULL, NULL, NULL, NULL),
(184, 5, '189', NULL, NULL, 'QUO-2026-121', '2026-06-08', '2026-07-08', 'Vinod Kumar', 'Erode\nErode, TamilNadu\n638052', 'Mr.Vinod Kumar,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'TTA-AA45', '2026-06-08 10:26:44', 1, 'Prices quoted are exclusive of GST unless otherwise specified. Quotation valid for 30 days.\r\n\r\n70% advance & 30% before dispatch. Dispatch after full payment only.\r\n\r\nSupply includes EV charger as per quotation. Installation and accessories excluded unless specified.\r\n\r\nDelivery: DC Fast Charger – 3–4 weeks | AC Charger – 1–2 weeks from order confirmation and advance payment.\r\n\r\n10 year warranty against manufacturing defects only.\r\n\r\nAMC & Support: Annual Maintenance Contract (AMC) available after the warranty period.\r\n\r\nOrders once confirmed cannot be cancelled or returned.\r\n\r\nLiability limited to invoice value.\r\n\r\nGoverned by Indian law. Jurisdiction: Madurai, Tamil Nadu.', NULL, NULL, NULL, NULL),
(185, 5, '190', NULL, NULL, 'QUO-2026-122', '2026-06-08', '2026-07-08', 'Balasubramani', 'Palladam\nCoimbatore, TamilNadu\n641664', 'Mr.Balasubramani,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'TTA-AA45', '2026-06-08 10:50:39', 1, 'Prices quoted are exclusive of GST unless otherwise specified. Quotation valid for 30 days.\r\n\r\n70% advance & 30% before dispatch. Dispatch after full payment only.\r\n\r\nSupply includes EV charger as per quotation. Installation and accessories excluded unless specified.\r\n\r\nDelivery: DC Fast Charger – 3–4 weeks | AC Charger – 1–2 weeks from order confirmation and advance payment.\r\n\r\n10 year warranty against manufacturing defects only.\r\n\r\nAMC & Support: Annual Maintenance Contract (AMC) available after the warranty period.\r\n\r\nOrders once confirmed cannot be cancelled or returned.\r\n\r\nLiability limited to invoice value.\r\n\r\nGoverned by Indian law. Jurisdiction: Madurai, Tamil Nadu.', NULL, NULL, NULL, NULL),
(186, 5, '113', 'ORD-037', '2026-06-09 06:35:22', 'QUO-2026-123', '2026-06-09', '2026-07-09', 'Rahul', 'Flat No 1/7, Kanchan Villa,\nAmravati, Maharashtra\n444608', 'Mr.Rahul,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'Y', 'Y', 'V1', '7', 'WMH-AA31', '2026-06-09 06:32:46', 1, 'Prices quoted are exclusive of GST unless otherwise specified. Quotation valid for 30 days.\r\n\r\n100 advance before dispatch. Dispatch after full payment only.\r\n\r\nSupply includes EV charger as per quotation. Installation and accessories excluded unless specified.\r\n\r\nDelivery: DC Fast Charger – 3–4 weeks | AC Charger – 1–2 weeks from order confirmation and advance payment.\r\n\r\n1 year warranty against manufacturing defects only.\r\n\r\nAMC & Support: Annual Maintenance Contract (AMC) available after the warranty period.\r\n\r\nOrders once confirmed cannot be cancelled or returned.\r\n\r\nLiability limited to invoice value.\r\n\r\nGoverned by Indian law. Jurisdiction: Madurai, Tamil Nadu.', NULL, NULL, NULL, NULL),
(187, 5, '191', NULL, NULL, 'QUO-2026-124', '2026-06-09', '2026-07-09', 'Karuna Sagar T', 'No.3, Bharath Villa, Jawahar 1st street, SS colony\nMadurai, TamilNadu\n625018', 'Mr.Karuna Sagar T,', 'Quotation for Electric Vehicle Charging Station', 'We hereby give our best quote based on our understanding...', '', 'N', 'N', 'V1', '8', 'TTA-AA45', '2026-06-09 08:04:30', 1, 'Prices quoted are exclusive of GST unless otherwise specified. Quotation valid for 30 days.\r\n\r\n70% advance & 30% before dispatch. Dispatch after full payment only.\r\n\r\nSupply includes EV charger as per quotation. Installation and accessories excluded unless specified.\r\n\r\nDelivery: DC Fast Charger – 3–4 weeks | AC Charger – 1–2 weeks from order confirmation and advance payment.\r\n\r\n1 year warranty against manufacturing defects only.\r\n\r\nAMC & Support: Annual Maintenance Contract (AMC) available after the warranty period.\r\n\r\nOrders once confirmed cannot be cancelled or returned.\r\n\r\nLiability limited to invoice value.\r\n\r\nGoverned by Indian law. Jurisdiction: Madurai, Tamil Nadu.', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `quotation_meta`
--

CREATE TABLE `quotation_meta` (
  `quotation_id` int(11) NOT NULL,
  `lead_name` varchar(255) NOT NULL,
  `quotation_date` date NOT NULL,
  `valid_till` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `quotation_summary_view`
-- (See below for the actual view)
--
CREATE TABLE `quotation_summary_view` (
`id` int(11)
,`parent_id` int(1)
,`client_id` varchar(50)
,`order_no` varchar(10)
,`order_date` timestamp
,`quotation_no` varchar(50)
,`date` date
,`valid_till` date
,`client_name` varchar(255)
,`client_address` varchar(255)
,`salutation` varchar(100)
,`subject` varchar(255)
,`introduction` text
,`additional_notes` text
,`order_status` char(1)
,`cp_status` char(1)
,`version_code` varchar(5)
,`cms_id` varchar(50)
,`cpo_id` varchar(50)
,`created_at` timestamp
,`year` int(11)
,`lead_id` int(11)
,`full_name` varchar(150)
,`phone_number` varchar(20)
,`city` varchar(255)
,`state` varchar(255)
,`customer_type_id` int(11)
,`customer_type_name` varchar(100)
,`total_products` bigint(21)
,`product_names` mediumtext
,`product_ids` mediumtext
);

-- --------------------------------------------------------

--
-- Table structure for table `quotation_terms`
--

CREATE TABLE `quotation_terms` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `terms` longtext DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quote_tracking`
--

CREATE TABLE `quote_tracking` (
  `tracking_id` varchar(20) NOT NULL,
  `quotation_id` int(11) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `revenue_messages`
--

CREATE TABLE `revenue_messages` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `revenue_messages`
--

INSERT INTO `revenue_messages` (`id`, `lead_id`, `message`, `created_at`) VALUES
(1, 8, '1', '2026-02-04 03:47:15');

-- --------------------------------------------------------

--
-- Table structure for table `summary`
--

CREATE TABLE `summary` (
  `id` int(11) NOT NULL,
  `quotation_id` int(11) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL,
  `net_value` decimal(12,2) NOT NULL,
  `total_discount` decimal(12,2) DEFAULT NULL,
  `gst_value` decimal(12,2) DEFAULT NULL,
  `grand_total` decimal(12,2) DEFAULT NULL,
  `payment_status` char(1) DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `summary`
--

INSERT INTO `summary` (`id`, `quotation_id`, `subtotal`, `net_value`, `total_discount`, `gst_value`, `grand_total`, `payment_status`) VALUES
(1, 1, '950000.00', '950000.00', '0.00', '47500.00', '997500.00', 'N'),
(2, 2, '950000.00', '900030.00', '49970.00', '45001.50', '945031.50', 'N'),
(3, 3, '680000.00', '650080.00', '29920.00', '32504.00', '682584.00', 'N'),
(4, 4, '57197.00', '47797.94', '9399.06', '2191.91', '49989.85', 'N'),
(5, 5, '21500.00', '21448.40', '51.60', '1072.42', '22520.82', 'N'),
(6, 6, '9000.00', '9000.00', '0.00', '450.00', '9450.00', 'N'),
(7, 7, '9220.00', '9220.00', '0.00', '489.60', '9709.60', 'N'),
(10, 10, '15400.00', '9199.19', '6200.81', '459.96', '9659.15', 'N'),
(11, 11, '15400.00', '9199.19', '6200.81', '459.96', '9659.15', 'N'),
(12, 12, '25000.00', '18000.00', '7000.00', '900.00', '18900.00', 'N'),
(13, 13, '12900.00', '12900.00', '0.00', '645.00', '13545.00', 'Y'),
(14, 14, '1385000.00', '1385000.00', '0.00', '69250.00', '1454250.00', 'N'),
(15, 15, '1757700.00', '1728199.60', '29500.40', '78209.98', '1806409.58', 'N'),
(16, 16, '7500.00', '7500.00', '0.00', '375.00', '7875.00', 'N'),
(32, 19, '850000.00', '830450.00', '19550.00', '41522.50', '871972.50', 'N'),
(40, 27, '1055000.00', '1055000.00', '0.00', '64000.00', '1119000.00', 'Y'),
(41, 28, '1050000.00', '1050000.00', '0.00', '66000.00', '1116000.00', 'N'),
(48, 35, '28000.00', '28000.00', '0.00', '1400.00', '29400.00', 'N'),
(52, 39, '19500.00', '19500.00', '0.00', '975.00', '20475.00', 'Y'),
(55, 42, '380000.00', '380000.00', '0.00', '19000.00', '399000.00', 'N'),
(56, 43, '650000.00', '650000.00', '0.00', '32500.00', '682500.00', 'N'),
(57, 44, '950000.00', '950000.00', '0.00', '47500.00', '997500.00', 'N'),
(86, 72, '400000.00', '400000.00', '0.00', '20000.00', '420000.00', 'N'),
(87, 73, '456000.00', '456000.00', '0.00', '22800.00', '478800.00', 'N'),
(91, 77, '850000.00', '850000.00', '0.00', '42500.00', '892500.00', 'N'),
(92, 78, '1300000.00', '1200030.00', '99970.00', '60001.50', '1260031.50', 'N'),
(93, 79, '1900000.00', '1760160.00', '139840.00', '88008.00', '1848168.00', 'N'),
(94, 80, '5610.00', '5610.00', '0.00', '294.80', '5904.80', 'Y'),
(95, 81, '1262467.00', '929438.49', '333028.51', '46471.92', '975910.41', 'N'),
(96, 82, '650000.00', '650000.00', '0.00', '32500.00', '682500.00', 'N'),
(97, 83, '23500.00', '23500.00', '0.00', '1175.00', '24675.00', 'N'),
(98, 84, '23500.00', '23500.00', '0.00', '1175.00', '24675.00', 'Y'),
(99, 85, '380000.00', '380000.00', '0.00', '19000.00', '399000.00', 'N'),
(100, 86, '1380000.00', '1380000.00', '0.00', '69000.00', '1449000.00', 'N'),
(101, 87, '188000.00', '188000.00', '0.00', '0.00', '188000.00', 'Y'),
(102, 88, '870000.00', '870000.00', '0.00', '46100.00', '916100.00', 'Y'),
(103, 89, '4500.00', '4500.00', '0.00', '225.00', '4725.00', 'N'),
(104, 90, '20110.00', '20110.00', '0.00', '1019.80', '21129.80', 'N'),
(105, 91, '130000.00', '130000.00', '0.00', '6500.00', '136500.00', 'N'),
(106, 92, '130000.00', '130000.00', '0.00', '6500.00', '136500.00', 'N'),
(107, 93, '130000.00', '130000.00', '0.00', '6500.00', '136500.00', 'N'),
(109, 95, '2122048.00', '2032880.49', '89167.51', '101644.02', '2134524.51', 'N'),
(110, 96, '27000.00', '27000.00', '0.00', '1428.00', '28428.00', 'Y'),
(116, 102, '15000.00', '15000.00', '0.00', '750.00', '15750.00', 'Y'),
(121, 107, '2986000.00', '2986000.00', '0.00', '149300.00', '3135300.00', 'N'),
(122, 108, '1145000.00', '1145000.00', '0.00', '81100.00', '1226100.00', 'N'),
(123, 109, '4200.00', '4200.00', '0.00', '210.00', '4410.00', 'N'),
(124, 110, '600000.00', '600000.00', '0.00', '30000.00', '630000.00', 'N'),
(125, 111, '4200.00', '4200.00', '0.00', '210.00', '4410.00', 'N'),
(126, 112, '4200.00', '4200.00', '0.00', '210.00', '4410.00', 'N'),
(127, 113, '600000.00', '600000.00', '0.00', '30000.00', '630000.00', 'N'),
(128, 114, '600000.00', '600000.00', '0.00', '30000.00', '630000.00', 'N'),
(129, 115, '4200.00', '4200.00', '0.00', '210.00', '4410.00', 'N'),
(130, 116, '4200.00', '4200.00', '0.00', '210.00', '4410.00', 'N'),
(131, 117, '4200.00', '4200.00', '0.00', '210.00', '4200.00', 'N'),
(133, 119, '2850000.00', '2550750.00', '299250.00', '127537.50', '2678287.50', 'N'),
(134, 120, '2850000.00', '2550750.00', '299250.00', '127537.50', '2678287.50', 'N'),
(135, 121, '93000.00', '93000.00', '0.00', '4650.00', '97650.00', 'N'),
(136, 122, '1270000.00', '1270000.00', '0.00', '105100.00', '1375100.00', 'N'),
(137, 123, '250000.00', '250000.00', '0.00', '12500.00', '262500.00', 'N'),
(138, 124, '2540000.00', '2540000.00', '0.00', '210200.00', '2750200.00', 'N'),
(139, 125, '1270000.00', '1270000.00', '0.00', '105100.00', '1375100.00', 'N'),
(140, 126, '38780.00', '38780.00', '0.00', '6980.40', '45760.40', 'N'),
(141, 127, '38780.00', '38780.00', '0.00', '6980.40', '45760.40', 'N'),
(142, 128, '38780.00', '38780.00', '0.00', '6980.40', '45760.40', 'N'),
(144, 130, '950000.00', '950000.00', '0.00', '47500.00', '997500.00', 'N'),
(145, 131, '1548000.00', '1548000.00', '0.00', '161640.00', '1709640.00', 'N'),
(146, 132, '38600.00', '38600.00', '0.00', '1930.00', '40530.00', 'N'),
(147, 133, '141000.00', '141000.00', '0.00', '7050.00', '148050.00', 'N'),
(148, 134, '1498000.00', '1498000.00', '0.00', '79900.00', '1577900.00', 'N'),
(149, 135, '1498000.00', '1498000.00', '0.00', '79900.00', '1577900.00', 'N'),
(150, 136, '1170100.00', '1170100.00', '0.00', '87118.00', '1257218.00', 'N'),
(151, 137, '1170100.00', '1170100.00', '0.00', '87118.00', '1257218.00', 'N'),
(152, 138, '6640000.00', '6640000.00', '0.00', '332000.00', '6972000.00', 'N'),
(153, 139, '1145000.00', '1145000.00', '0.00', '81100.00', '1226100.00', 'N'),
(154, 140, '975000.00', '975000.00', '0.00', '91000.00', '1066000.00', 'N'),
(155, 141, '950000.00', '950000.00', '0.00', '47500.00', '997500.00', 'N'),
(156, 142, '1548000.00', '1548000.00', '0.00', '161640.00', '1709640.00', 'N'),
(158, 144, '950000.00', '950000.00', '0.00', '86500.00', '1036500.00', 'N'),
(159, 145, '950000.00', '950000.00', '0.00', '86500.00', '1036500.00', 'N'),
(160, 146, '950000.00', '950000.00', '0.00', '86500.00', '1036500.00', 'N'),
(162, 148, '2850000.00', '2550750.00', '299250.00', '127537.50', '2678287.50', 'N'),
(163, 149, '2850000.00', '2550750.00', '299250.00', '127537.50', '2678287.50', 'N'),
(164, 150, '1270000.00', '1270000.00', '0.00', '105100.00', '1375100.00', 'N'),
(165, 151, '2540000.00', '2540000.00', '0.00', '210200.00', '2750200.00', 'N'),
(169, 155, '1488000.00', '1488000.00', '0.00', '157340.00', '1645340.00', 'N'),
(178, 164, '16000.00', '15870.00', '130.00', '793.50', '16663.50', 'N'),
(179, 165, '16000.00', '15870.00', '130.00', '793.50', '16663.50', 'N'),
(181, 167, '1145000.00', '1145000.00', '0.00', '81100.00', '1226100.00', 'N'),
(182, 168, '1488000.00', '1488000.00', '0.00', '157340.00', '1645340.00', 'N'),
(183, 169, '630000.00', '630000.00', '0.00', '67900.00', '697900.00', 'N'),
(186, 172, '850000.00', '850000.00', '0.00', '42500.00', '892500.00', 'N'),
(187, 173, '141000.00', '141000.00', '0.00', '7050.00', '148050.00', 'N'),
(188, 174, '141000.00', '141000.00', '0.00', '7050.00', '148050.00', 'N'),
(189, 175, '1145000.00', '1145000.00', '0.00', '81100.00', '1226100.00', 'N'),
(190, 176, '22500.00', '22500.00', '0.00', '1190.00', '23690.00', 'N'),
(191, 177, '380000.00', '380000.00', '0.00', '19000.00', '399000.00', 'N'),
(192, 178, '380000.00', '380000.00', '0.00', '19000.00', '399000.00', 'N'),
(193, 179, '780000.00', '780000.00', '0.00', '39000.00', '819000.00', 'N'),
(194, 180, '10000.00', '10000.00', '0.00', '500.00', '10500.00', 'Y'),
(195, 181, '650000.00', '650000.00', '0.00', '32500.00', '682500.00', 'N'),
(196, 182, '1650000.00', '1650000.00', '0.00', '154000.00', '1804000.00', 'N'),
(197, 183, '1650000.00', '1650000.00', '0.00', '154000.00', '1804000.00', 'N'),
(198, 184, '1200000.00', '1200000.00', '0.00', '105500.00', '1305500.00', 'N'),
(199, 185, '1654000.00', '1654000.00', '0.00', '100400.00', '1754400.00', 'N'),
(200, 186, '4500.00', '4500.00', '0.00', '225.00', '4725.00', 'Y'),
(201, 187, '1855000.00', '1855000.00', '0.00', '151900.00', '2006900.00', 'N');

-- --------------------------------------------------------

--
-- Structure for view `quotation_summary_view`
--
DROP TABLE IF EXISTS `quotation_summary_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`cloud`@`%` SQL SECURITY DEFINER VIEW `quotation_summary_view`  AS SELECT `q`.`id` AS `id`, `q`.`parent_id` AS `parent_id`, `q`.`client_id` AS `client_id`, `q`.`order_no` AS `order_no`, `q`.`order_date` AS `order_date`, `q`.`quotation_no` AS `quotation_no`, `q`.`date` AS `date`, `q`.`valid_till` AS `valid_till`, `q`.`client_name` AS `client_name`, `q`.`client_address` AS `client_address`, `q`.`salutation` AS `salutation`, `q`.`subject` AS `subject`, `q`.`introduction` AS `introduction`, `q`.`additional_notes` AS `additional_notes`, `q`.`order_status` AS `order_status`, `q`.`cp_status` AS `cp_status`, `q`.`version_code` AS `version_code`, `q`.`cms_id` AS `cms_id`, `q`.`cpo_id` AS `cpo_id`, `q`.`created_at` AS `created_at`, `q`.`year` AS `year`, `l`.`id` AS `lead_id`, `l`.`full_name` AS `full_name`, `l`.`phone_number` AS `phone_number`, `l`.`city` AS `city`, `l`.`state` AS `state`, `l`.`customer_type_id` AS `customer_type_id`, `ct`.`type_name` AS `customer_type_name`, `ps`.`total_products` AS `total_products`, `ps`.`product_names` AS `product_names`, `ps`.`product_ids` AS `product_ids` FROM (((`quotations` `q` left join `leads` `l` on(`l`.`id` = `q`.`client_id`)) left join `customer_types` `ct` on(`ct`.`id` = `l`.`customer_type_id`)) left join (select `p`.`quotation_id` AS `quotation_id`,count(`p`.`product_id`) AS `total_products`,group_concat(`p`.`product_name` separator ', ') AS `product_names`,group_concat(`p`.`product_id` separator ', ') AS `product_ids` from `productss` `p` group by `p`.`quotation_id`) `ps` on(`ps`.`quotation_id` = `q`.`id`)) WHERE `q`.`order_status` = 'Y''Y'  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bank_details`
--
ALTER TABLE `bank_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_id` (`quotation_id`);

--
-- Indexes for table `cpo_activation_links`
--
ALTER TABLE `cpo_activation_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `customer_types`
--
ALTER TABLE `customer_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_types_old`
--
ALTER TABLE `customer_types_old`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dealer_details`
--
ALTER TABLE `dealer_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `franchise_messages`
--
ALTER TABLE `franchise_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inquiries_messages`
--
ALTER TABLE `inquiries_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_summary`
--
ALTER TABLE `inventory_summary`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventry_payments`
--
ALTER TABLE `inventry_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_type_id` (`customer_type_id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `source_id` (`source_id`);

--
-- Indexes for table `lead_messages`
--
ALTER TABLE `lead_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lead_sources`
--
ALTER TABLE `lead_sources`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lead_statuses`
--
ALTER TABLE `lead_statuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_details`
--
ALTER TABLE `login_details`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `productss`
--
ALTER TABLE `productss`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quotation_meta`
--
ALTER TABLE `quotation_meta`
  ADD PRIMARY KEY (`quotation_id`);

--
-- Indexes for table `quotation_terms`
--
ALTER TABLE `quotation_terms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quote_tracking`
--
ALTER TABLE `quote_tracking`
  ADD PRIMARY KEY (`tracking_id`);

--
-- Indexes for table `revenue_messages`
--
ALTER TABLE `revenue_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `summary`
--
ALTER TABLE `summary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_id` (`quotation_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bank_details`
--
ALTER TABLE `bank_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=188;

--
-- AUTO_INCREMENT for table `cpo_activation_links`
--
ALTER TABLE `cpo_activation_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `customer_types`
--
ALTER TABLE `customer_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `customer_types_old`
--
ALTER TABLE `customer_types_old`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `dealer_details`
--
ALTER TABLE `dealer_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `franchise_messages`
--
ALTER TABLE `franchise_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inquiries_messages`
--
ALTER TABLE `inquiries_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory_summary`
--
ALTER TABLE `inventory_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `inventry_payments`
--
ALTER TABLE `inventry_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=192;

--
-- AUTO_INCREMENT for table `lead_messages`
--
ALTER TABLE `lead_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lead_sources`
--
ALTER TABLE `lead_sources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `lead_statuses`
--
ALTER TABLE `lead_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=183;

--
-- AUTO_INCREMENT for table `productss`
--
ALTER TABLE `productss`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=396;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=188;

--
-- AUTO_INCREMENT for table `quotation_meta`
--
ALTER TABLE `quotation_meta`
  MODIFY `quotation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation_terms`
--
ALTER TABLE `quotation_terms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `revenue_messages`
--
ALTER TABLE `revenue_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `summary`
--
ALTER TABLE `summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bank_details`
--
ALTER TABLE `bank_details`
  ADD CONSTRAINT `bank_details_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leads`
--
ALTER TABLE `leads`
  ADD CONSTRAINT `leads_ibfk_1` FOREIGN KEY (`customer_type_id`) REFERENCES `customer_types` (`id`),
  ADD CONSTRAINT `leads_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `lead_statuses` (`id`),
  ADD CONSTRAINT `leads_ibfk_3` FOREIGN KEY (`source_id`) REFERENCES `lead_sources` (`id`);

--
-- Constraints for table `summary`
--
ALTER TABLE `summary`
  ADD CONSTRAINT `summary_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
