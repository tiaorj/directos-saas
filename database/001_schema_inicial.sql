SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[OS_Assinaturas](
	[AssinaturaId] [int] IDENTITY(1,1) NOT NULL,
	[EmpresaId] [int] NOT NULL,
	[PlanoId] [int] NOT NULL,
	[Status] [varchar](30) NOT NULL,
	[DataInicio] [datetime] NOT NULL,
	[DataFim] [datetime] NULL,
	[DataCadastro] [datetime] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[AssinaturaId] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[OS_Auditoria]    Script Date: 03/06/2026 14:34:53 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[OS_Auditoria](
	[AuditoriaId] [int] IDENTITY(1,1) NOT NULL,
	[EmpresaId] [int] NULL,
	[UsuarioId] [int] NULL,
	[Acao] [varchar](100) NOT NULL,
	[Entidade] [varchar](100) NULL,
	[EntidadeId] [int] NULL,
	[Descricao] [varchar](max) NULL,
	[IpAcesso] [varchar](50) NULL,
	[UserAgent] [varchar](500) NULL,
	[DataRegistro] [datetime] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[AuditoriaId] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  Table [dbo].[OS_CamposPersonalizados]    Script Date: 03/06/2026 14:34:53 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[OS_CamposPersonalizados](
	[CampoId] [int] IDENTITY(1,1) NOT NULL,
	[EmpresaId] [int] NOT NULL,
	[NomeCampo] [varchar](100) NOT NULL,
	[Rotulo] [varchar](150) NOT NULL,
	[TipoCampo] [varchar](50) NOT NULL,
	[Obrigatorio] [bit] NOT NULL,
	[Ordem] [int] NOT NULL,
	[Ativo] [bit] NOT NULL,
	[DataCadastro] [datetime] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[CampoId] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[OS_Clientes]    Script Date: 03/06/2026 14:34:53 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[OS_Clientes](
	[ClienteId] [int] IDENTITY(1,1) NOT NULL,
	[Nome] [varchar](150) NOT NULL,
	[Telefone] [varchar](30) NULL,
	[Email] [varchar](150) NULL,
	[Documento] [varchar](30) NULL,
	[Endereco] [varchar](255) NULL,
	[Cidade] [varchar](100) NULL,
	[Estado] [varchar](2) NULL,
	[Ativo] [bit] NOT NULL,
	[DataCadastro] [datetime] NOT NULL,
	[EmpresaId] [int] NULL,
PRIMARY KEY CLUSTERED 
(
	[ClienteId] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[OS_Empresas]    Script Date: 03/06/2026 14:34:53 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[OS_Empresas](
	[EmpresaId] [int] IDENTITY(1,1) NOT NULL,
	[NomeFantasia] [varchar](150) NOT NULL,
	[RazaoSocial] [varchar](150) NULL,
	[Cnpj] [varchar](20) NULL,
	[Email] [varchar](150) NULL,
	[Telefone] [varchar](20) NULL,
	[WhatsApp] [varchar](20) NULL,
	[Slug] [varchar](80) NOT NULL,
	[Ativo] [bit] NOT NULL,
	[DataCadastro] [datetime] NOT NULL,
	[OcultarOnboarding] [bit] NOT NULL,
	[Segmento] [varchar](50) NULL,
PRIMARY KEY CLUSTERED 
(
	[EmpresaId] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[OS_Historico]    Script Date: 03/06/2026 14:34:53 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[OS_Historico](
	[HistoricoId] [int] IDENTITY(1,1) NOT NULL,
	[OrdemServicoId] [int] NOT NULL,
	[UsuarioId] [int] NOT NULL,
	[StatusAnterior] [varchar](50) NULL,
	[StatusNovo] [varchar](50) NULL,
	[Descricao] [text] NULL,
	[DataRegistro] [datetime] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[HistoricoId] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  Table [dbo].[OS_MensagensWhatsApp]    Script Date: 03/06/2026 14:34:53 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[OS_MensagensWhatsApp](
	[MensagemWhatsAppId] [int] IDENTITY(1,1) NOT NULL,
	[EmpresaId] [int] NOT NULL,
	[OrdemServicoId] [int] NOT NULL,
	[UsuarioId] [int] NULL,
	[TipoMensagem] [varchar](50) NOT NULL,
	[Origem] [varchar](50) NOT NULL,
	[Telefone] [varchar](30) NULL,
	[Mensagem] [nvarchar](max) NOT NULL,
	[DataCadastro] [datetime] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[MensagemWhatsAppId] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  Table [dbo].[OS_OrdensServico]    Script Date: 03/06/2026 14:34:53 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[OS_OrdensServico](
	[OrdemServicoId] [int] IDENTITY(1,1) NOT NULL,
	[ClienteId] [int] NOT NULL,
	[ServicoId] [int] NULL,
	[Titulo] [varchar](150) NOT NULL,
	[DescricaoProblema] [text] NULL,
	[DescricaoSolucao] [text] NULL,
	[Status] [varchar](50) NOT NULL,
	[Prioridade] [varchar](30) NOT NULL,
	[ValorPrevisto] [decimal](10, 2) NULL,
	[ValorFinal] [decimal](10, 2) NULL,
	[DataAbertura] [datetime] NOT NULL,
	[DataPrevisao] [date] NULL,
	[DataConclusao] [datetime] NULL,
	[Observacao] [text] NULL,
	[CodigoOS] [varchar](20) NULL,
	[EmpresaId] [int] NULL,
	[TokenAcompanhamento] [uniqueidentifier] NOT NULL,
	[MostrarValorCliente] [bit] NOT NULL,
	[MostrarSolucaoCliente] [bit] NOT NULL,
	[MostrarHistoricoCliente] [bit] NOT NULL,
	[StatusFinanceiro] [varchar](30) NOT NULL,
	[FormaPagamento] [varchar](50) NULL,
	[ValorPago] [decimal](18, 2) NULL,
	[DataPagamento] [datetime] NULL,
	[ObservacaoFinanceira] [nvarchar](max) NULL,
PRIMARY KEY CLUSTERED 
(
	[OrdemServicoId] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  Table [dbo].[OS_OrdensServicoAnexos]    Script Date: 03/06/2026 14:34:53 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[OS_OrdensServicoAnexos](
	[AnexoId] [int] IDENTITY(1,1) NOT NULL,
	[OrdemServicoId] [int] NOT NULL,
	[EmpresaId] [int] NOT NULL,
	[UsuarioId] [int] NULL,
	[NomeOriginal] [varchar](255) NOT NULL,
	[NomeArquivo] [varchar](255) NOT NULL,
	[CaminhoArquivo] [varchar](500) NOT NULL,
	[TipoArquivo] [varchar](100) NULL,
	[TamanhoBytes] [int] NULL,
	[VisivelCliente] [bit] NOT NULL,
	[DataCadastro] [datetime] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[AnexoId] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[OS_OrdensServicoCampos]    Script Date: 03/06/2026 14:34:53 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[OS_OrdensServicoCampos](
	[ValorCampoId] [int] IDENTITY(1,1) NOT NULL,
	[EmpresaId] [int] NOT NULL,
	[OrdemServicoId] [int] NOT NULL,
	[CampoId] [int] NOT NULL,
	[Valor] [nvarchar](max) NULL,
	[DataCadastro] [datetime] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[ValorCampoId] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  Table [dbo].[OS_OrdensServicoHistorico]    Script Date: 03/06/2026 14:34:53 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[OS_OrdensServicoHistorico](
	[HistoricoId] [int] IDENTITY(1,1) NOT NULL,
	[OrdemServicoId] [int] NOT NULL,
	[UsuarioId] [int] NULL,
	[StatusAnterior] [varchar](50) NULL,
	[StatusNovo] [varchar](50) NULL,
	[Descricao] [varchar](max) NOT NULL,
	[VisivelCliente] [bit] NOT NULL,
	[DataCadastro] [datetime] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[HistoricoId] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  Table [dbo].[OS_Planos]    Script Date: 03/06/2026 14:34:53 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[OS_Planos](
	[PlanoId] [int] IDENTITY(1,1) NOT NULL,
	[Nome] [varchar](100) NOT NULL,
	[Slug] [varchar](50) NOT NULL,
	[Descricao] [varchar](255) NULL,
	[LimiteOSMes] [int] NULL,
	[LimiteUsuarios] [int] NULL,
	[PermiteAnexos] [bit] NOT NULL,
	[PermiteAreaCliente] [bit] NOT NULL,
	[PermiteWhatsapp] [bit] NOT NULL,
	[ValorMensal] [decimal](18, 2) NOT NULL,
	[Ativo] [bit] NOT NULL,
	[DataCadastro] [datetime] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[PlanoId] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[OS_Recebimentos]    Script Date: 03/06/2026 14:34:53 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[OS_Recebimentos](
	[RecebimentoId] [int] IDENTITY(1,1) NOT NULL,
	[EmpresaId] [int] NOT NULL,
	[OrdemServicoId] [int] NOT NULL,
	[UsuarioId] [int] NULL,
	[ValorRecebido] [decimal](18, 2) NOT NULL,
	[FormaPagamento] [varchar](50) NULL,
	[DataRecebimento] [date] NOT NULL,
	[Observacao] [nvarchar](max) NULL,
	[DataCadastro] [datetime] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[RecebimentoId] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  Table [dbo].[OS_Servicos]    Script Date: 03/06/2026 14:34:53 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[OS_Servicos](
	[ServicoId] [int] IDENTITY(1,1) NOT NULL,
	[Nome] [varchar](150) NOT NULL,
	[Descricao] [varchar](500) NULL,
	[ValorBase] [decimal](10, 2) NULL,
	[Ativo] [bit] NOT NULL,
	[DataCadastro] [datetime] NOT NULL,
	[EmpresaId] [int] NULL,
	[ChecklistPadrao] [nvarchar](max) NULL,
PRIMARY KEY CLUSTERED 
(
	[ServicoId] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  Table [dbo].[OS_Usuarios]    Script Date: 03/06/2026 14:34:53 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[OS_Usuarios](
	[UsuarioId] [int] IDENTITY(1,1) NOT NULL,
	[Nome] [varchar](150) NOT NULL,
	[Email] [varchar](150) NOT NULL,
	[SenhaHash] [varchar](255) NOT NULL,
	[Perfil] [varchar](50) NOT NULL,
	[Ativo] [bit] NOT NULL,
	[DataCadastro] [datetime] NOT NULL,
	[EmpresaId] [int] NULL,
PRIMARY KEY CLUSTERED 
(
	[UsuarioId] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[Email] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[PortfoliioEmpresa]    Script Date: 03/06/2026 14:34:53 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[PortfoliioEmpresa](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[servico] [varchar](100) NULL,
	[cliente] [varchar](100) NULL,
	[descricao_case] [text] NULL,
	[resultado] [varchar](255) NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
ALTER TABLE [dbo].[OS_Assinaturas] ADD  DEFAULT ('Ativa') FOR [Status]
GO
ALTER TABLE [dbo].[OS_Assinaturas] ADD  DEFAULT (getdate()) FOR [DataInicio]
GO
ALTER TABLE [dbo].[OS_Assinaturas] ADD  DEFAULT (getdate()) FOR [DataCadastro]
GO
ALTER TABLE [dbo].[OS_Auditoria] ADD  DEFAULT (getdate()) FOR [DataRegistro]
GO
ALTER TABLE [dbo].[OS_CamposPersonalizados] ADD  DEFAULT ((0)) FOR [Obrigatorio]
GO
ALTER TABLE [dbo].[OS_CamposPersonalizados] ADD  DEFAULT ((0)) FOR [Ordem]
GO
ALTER TABLE [dbo].[OS_CamposPersonalizados] ADD  DEFAULT ((1)) FOR [Ativo]
GO
ALTER TABLE [dbo].[OS_CamposPersonalizados] ADD  DEFAULT (getdate()) FOR [DataCadastro]
GO
ALTER TABLE [dbo].[OS_Clientes] ADD  DEFAULT ((1)) FOR [Ativo]
GO
ALTER TABLE [dbo].[OS_Clientes] ADD  DEFAULT (getdate()) FOR [DataCadastro]
GO
ALTER TABLE [dbo].[OS_Empresas] ADD  DEFAULT ((1)) FOR [Ativo]
GO
ALTER TABLE [dbo].[OS_Empresas] ADD  DEFAULT (getdate()) FOR [DataCadastro]
GO
ALTER TABLE [dbo].[OS_Empresas] ADD  DEFAULT ((0)) FOR [OcultarOnboarding]
GO
ALTER TABLE [dbo].[OS_Historico] ADD  DEFAULT (getdate()) FOR [DataRegistro]
GO
ALTER TABLE [dbo].[OS_MensagensWhatsApp] ADD  DEFAULT (getdate()) FOR [DataCadastro]
GO
ALTER TABLE [dbo].[OS_OrdensServico] ADD  DEFAULT ('Aberta') FOR [Status]
GO
ALTER TABLE [dbo].[OS_OrdensServico] ADD  DEFAULT ('Normal') FOR [Prioridade]
GO
ALTER TABLE [dbo].[OS_OrdensServico] ADD  DEFAULT (getdate()) FOR [DataAbertura]
GO
ALTER TABLE [dbo].[OS_OrdensServico] ADD  DEFAULT (newid()) FOR [TokenAcompanhamento]
GO
ALTER TABLE [dbo].[OS_OrdensServico] ADD  CONSTRAINT [DF_OS_OrdensServico_MostrarValorCliente]  DEFAULT ((1)) FOR [MostrarValorCliente]
GO
ALTER TABLE [dbo].[OS_OrdensServico] ADD  CONSTRAINT [DF_OS_OrdensServico_MostrarSolucaoCliente]  DEFAULT ((1)) FOR [MostrarSolucaoCliente]
GO
ALTER TABLE [dbo].[OS_OrdensServico] ADD  CONSTRAINT [DF_OS_OrdensServico_MostrarHistoricoCliente]  DEFAULT ((1)) FOR [MostrarHistoricoCliente]
GO
ALTER TABLE [dbo].[OS_OrdensServico] ADD  DEFAULT ('Pendente') FOR [StatusFinanceiro]
GO
ALTER TABLE [dbo].[OS_OrdensServicoAnexos] ADD  DEFAULT ((0)) FOR [VisivelCliente]
GO
ALTER TABLE [dbo].[OS_OrdensServicoAnexos] ADD  DEFAULT (getdate()) FOR [DataCadastro]
GO
ALTER TABLE [dbo].[OS_OrdensServicoCampos] ADD  DEFAULT (getdate()) FOR [DataCadastro]
GO
ALTER TABLE [dbo].[OS_OrdensServicoHistorico] ADD  DEFAULT ((1)) FOR [VisivelCliente]
GO
ALTER TABLE [dbo].[OS_OrdensServicoHistorico] ADD  DEFAULT (getdate()) FOR [DataCadastro]
GO
ALTER TABLE [dbo].[OS_Planos] ADD  DEFAULT ((1)) FOR [PermiteAnexos]
GO
ALTER TABLE [dbo].[OS_Planos] ADD  DEFAULT ((1)) FOR [PermiteAreaCliente]
GO
ALTER TABLE [dbo].[OS_Planos] ADD  DEFAULT ((1)) FOR [PermiteWhatsapp]
GO
ALTER TABLE [dbo].[OS_Planos] ADD  DEFAULT ((0)) FOR [ValorMensal]
GO
ALTER TABLE [dbo].[OS_Planos] ADD  DEFAULT ((1)) FOR [Ativo]
GO
ALTER TABLE [dbo].[OS_Planos] ADD  DEFAULT (getdate()) FOR [DataCadastro]
GO
ALTER TABLE [dbo].[OS_Recebimentos] ADD  DEFAULT (getdate()) FOR [DataCadastro]
GO
ALTER TABLE [dbo].[OS_Servicos] ADD  DEFAULT ((1)) FOR [Ativo]
GO
ALTER TABLE [dbo].[OS_Servicos] ADD  DEFAULT (getdate()) FOR [DataCadastro]
GO
ALTER TABLE [dbo].[OS_Usuarios] ADD  DEFAULT ('Admin') FOR [Perfil]
GO
ALTER TABLE [dbo].[OS_Usuarios] ADD  DEFAULT ((1)) FOR [Ativo]
GO
ALTER TABLE [dbo].[OS_Usuarios] ADD  DEFAULT (getdate()) FOR [DataCadastro]
GO
ALTER TABLE [dbo].[OS_Historico]  WITH CHECK ADD FOREIGN KEY([OrdemServicoId])
REFERENCES [dbo].[OS_OrdensServico] ([OrdemServicoId])
GO
ALTER TABLE [dbo].[OS_Historico]  WITH CHECK ADD FOREIGN KEY([UsuarioId])
REFERENCES [dbo].[OS_Usuarios] ([UsuarioId])
GO
ALTER TABLE [dbo].[OS_OrdensServico]  WITH CHECK ADD FOREIGN KEY([ClienteId])
REFERENCES [dbo].[OS_Clientes] ([ClienteId])
GO
ALTER TABLE [dbo].[OS_OrdensServico]  WITH CHECK ADD FOREIGN KEY([ServicoId])
REFERENCES [dbo].[OS_Servicos] ([ServicoId])
GO
