/****** Object:  Table [dbo].[pastes]    Script Date: 13-6-2017 18:18:41 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[pastes](
	[ident] [nvarchar](32) NOT NULL,
	[date] [datetime] NOT NULL,
	[text] [nvarchar](max) NOT NULL,
	[mimetype] [nvarchar](255) NULL,
	[ip] [nvarchar](255) NULL,
	[expires] [datetime] NULL,
 CONSTRAINT [PK_pastes] PRIMARY KEY CLUSTERED 
(
	[ident] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]

GO
