var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.WorkspaceWidget = Y.Base.create('workspaceWidget', SYS.AppWidget, [
        SYS.AppWorkspace
    ], {}, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            defaultPage: {
                value: {
                    component: 'teamList',
                    widget: 'TeamListWidget'
                }
            }
        }
    });

    NS.ws = SYS.AppWorkspace.build('{C#MODNAME}', NS.WorkspaceWidget);

    NS.TeamHeaderWidget = Y.Base.create('TeamHeaderWidget', SYS.AppWidget, [], {
        buildTData: function(){
            var team = this.get('team');
            return {
                teamid: team.get('id'),
                title: team.get('title'),
            }
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'header'},
            team: {}
        },
        CLICKS: {},
    });

    var TeamAppWorkspacePage = function(p){
        this._isTeamPage = true;
        TeamAppWorkspacePage.superclass.constructor.apply(this, arguments);
    };
    Y.extend(TeamAppWorkspacePage, SYS.AppWorkspacePage, {
        init: function(p){
            if (!p._isTeamPage){
                this.teamid = p.component | 0;
                p.module = p.widget;
                p.component = p.args[0] || '';
                p.widget = p.args[1] || '';

                var args = [];
                if (p.args.length > 2){ // clone
                    for (var i = 2; i < p.args.length; i++){
                        args[args.length] = p.args[i];
                    }
                }
                p.args = args;
            } else {
                this.teamid = p.teamid;
            }
            TeamAppWorkspacePage.superclass.init.call(this, p);
        },
    });
    NS.TeamAppWorkspacePage = TeamAppWorkspacePage;

    NS.WorkspaceItemWidget = Y.Base.create('WorkspaceItemWidget', SYS.AppWidget, [
        SYS.AppWorkspace,
        SYS.ContainerWidgetExt,
    ], {
        getDefaultPage: function(module){
            var AppWorkspacePage = this.get('AppWorkspacePage'),
                page = new AppWorkspacePage(),
                team = this.get('team');

            page.teamid = team.get('id');
            page.module = module || team.get('module');
            page.component = 'teamPlugin';
            page.widget = 'TeamPluginWidget';

            return page;
        },
        showWorkspacePage: function(page){
            var AppWorkspacePage = this.get('AppWorkspacePage'),
                page = new AppWorkspacePage(page),
                team = this.get('team');

            if (!team || team.get('id') !== page.teamid){
                this._reloadTeam(page);
            } else {
                if (page.isEmpty()){
                    page = this.getDefaultPage(page.module);
                }
                this._renderTeamMenu(page);
                this._showWorkspacePage(page);
            }
        },
        _reloadTeam: function(page){
            this.set('waiting', true);
            this.get('appInstance').team(page.teamid, function(err, result){
                this.set('waiting', false);
                this._renderTeam(err ? null : result.team, page);
            }, this);
        },
        _renderTeam: function(team, page){
            this.set('team', team);

            this.cleanWidgets();

            if (!team){
                // TODO: show 404 info
                return;
            }

            var tp = this.template;

            this.addWidget('header', new NS.TeamHeaderWidget({
                srcNode: tp.append('headerWidget', '<div></div>'),
                team: team
            }));

            var pluginList = this.get('appInstance').get('pluginList'),
                mods = pluginList.getModules(team);

            NS.uses(mods, 'teamPlugin', function(){
                this.set('waiting', false);
                this._onLoadPluginComponents(page);
            }, this);
        },
        _renderTeamMenu: function(page){
            var tp = this.template,
                team = this.get('team'),
                mods = this.get('appInstance').get('pluginList').getModules(team);

            this.removeWidget(function(n){
                return n.split('-')[0] === 'menu';
            });

            mods = mods.sort(function(m1, m2){
                if (m1 === page.module){
                    return -1;
                }
                if (m2 === page.module){
                    return 1;
                }
                return 0;
            });

            for (var i = 0, name, MenuWidget; i < mods.length; i++){
                name = mods[i];

                if (!(MenuWidget = Brick.mod[name].TeamWSMenuWidget)){
                    continue;
                }

                this.addWidget('menu-' + name, new MenuWidget({
                    srcNode: tp.append('menuWidget', '<div></div>'),
                    team: team,
                    page: page
                }));
            }
        },
        _onLoadPluginComponents: function(page){
            var team = this.get('team');

            if (page.isEmpty()){
                page = this.getDefaultPage(page.module);
            }

            this._renderTeamMenu(page);

            this._showWorkspacePage(page);
        },
        onFillWidgetOptions: function(options){
            return Y.mix({
                team: this.get('team')
            }, options);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'team'},
            AppWorkspacePage: {
                value: NS.TeamAppWorkspacePage
            },
            team: NS.ATTRIBUTE.team,
        }
    });

    NS.item = SYS.AppWorkspace.build('{C#MODNAME}', NS.WorkspaceItemWidget);
};
