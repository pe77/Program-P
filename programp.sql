--
-- Banco de Dados: `programp`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `data`
--

CREATE TABLE IF NOT EXISTS `data` (
  `unique` varchar(41) NOT NULL,
  `data` text NOT NULL,
  UNIQUE KEY `unique` (`unique`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura da tabela `log`
--

CREATE TABLE IF NOT EXISTS `log` (
  `user` varchar(255) NOT NULL,
  `input` text NOT NULL,
  `response` text NOT NULL,
  `bot` varchar(255) NOT NULL,
  `data` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura da tabela `prop`
--

CREATE TABLE IF NOT EXISTS `prop` (
  `unique` varchar(255) NOT NULL,
  `type` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  KEY `unique` (`unique`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
