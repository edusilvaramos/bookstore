<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN', message: 'You must be an admin to access this page.')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
        

    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Bookstore Admin Dashboard')
            ->setDefaultColorScheme('dark')
            ->setFaviconPath('favicon.ico')
            ->setLocales([
                'en' => 'English',
                'fr' => 'Français',
                'pt' => 'Português',

            ]);

    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(BookCrudController::class, 'Books', 'fas fa-book');
        yield MenuItem::linkTo(UserCrudController::class, 'Users', 'fas fa-user');
    }
}
